<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\ExpressCallback;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Swag\PayPal\Diagnostics\ExpressCallback\CallbackUrlBuilder;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackDiagnostics;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFinding;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFindingType;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackStatus;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\CallbackOutcome;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\TrustedHosts;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Severity;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressCallbackDiagnostics::class)]
class ExpressCallbackDiagnosticsTest extends TestCase
{
    private const PAYPAL_PAYMENT_METHOD_ID = '0190a0e5b4f97d6c8a4ad3d8f0c6b1a2';

    private const ACTIVE_SETTINGS = [
        Settings::ECS_CART_ENABLED => true,
        Settings::ECS_SHIPPING_CALLBACK_ENABLED => true,
    ];

    private string $salesChannelId;

    protected function setUp(): void
    {
        $this->salesChannelId = Uuid::randomHex();
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('statusProvider')]
    public function testStatus(array $settings, SalesChannelEntity $salesChannel, ExpressCallbackStatus $status): void
    {
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, $settings)->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertSame($status, $result->status);
    }

    public static function statusProvider(): \Generator
    {
        yield 'PayPal not assigned' => [self::ACTIVE_SETTINGS, self::createSalesChannel(payPal: null), ExpressCallbackStatus::Inactive];
        yield 'PayPal deactivated' => [self::ACTIVE_SETTINGS, self::createSalesChannel(payPal: false), ExpressCallbackStatus::Inactive];
        yield 'storefront without Express Checkout buttons' => [
            [...self::ACTIVE_SETTINGS, Settings::ECS_CART_ENABLED => false],
            self::createSalesChannel(),
            ExpressCallbackStatus::Inactive,
        ];
        yield 'headless frontends don\'t need the buttons' => [
            [...self::ACTIVE_SETTINGS, Settings::ECS_CART_ENABLED => false],
            self::createSalesChannel(typeId: Defaults::SALES_CHANNEL_TYPE_API),
            ExpressCallbackStatus::Active,
        ];
        yield 'local development environment' => [
            [...self::ACTIVE_SETTINGS, Settings::IS_LOCAL_ENVIRONMENT => true],
            self::createSalesChannel(),
            ExpressCallbackStatus::LocalEnvironment,
        ];
        yield 'shipping method choice turned off' => [
            [...self::ACTIVE_SETTINGS, Settings::ECS_SHIPPING_CALLBACK_ENABLED => false],
            self::createSalesChannel(),
            ExpressCallbackStatus::Disabled,
        ];
        yield 'active' => [self::ACTIVE_SETTINGS, self::createSalesChannel(), ExpressCallbackStatus::Active];
    }

    public function testFindingsOfAnActiveStorefrontMostSevereFirst(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);

        $diagnostics = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS, CallbackOutcome::Failed, sandbox: true);
        $result = $diagnostics->check($this->salesChannelId, Context::createDefaultContext(), true);

        static::assertEquals([
            new ExpressCallbackFinding(ExpressCallbackFindingType::LastCallbackFailed),
            new ExpressCallbackFinding(ExpressCallbackFindingType::UntrustedProxy),
            new ExpressCallbackFinding(ExpressCallbackFindingType::Sandbox),
        ], $result->findings);
        static::assertSame(CallbackOutcome::Failed, $result->lastCallback?->outcome);
    }

    public function testHeadlessSalesChannelsOnlyNeedTheStoreApiUrl(): void
    {
        $salesChannel = self::createSalesChannel(typeId: Defaults::SALES_CHANNEL_TYPE_API);
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS)->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertEquals([new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiUrlUnknown)], $result->findings);
    }

    public function testFindingsOfAHeadlessSalesChannelMostSevereFirst(): void
    {
        $salesChannel = self::createSalesChannel(typeId: Defaults::SALES_CHANNEL_TYPE_API);
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS, sandbox: true)->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertEquals([
            new ExpressCallbackFinding(ExpressCallbackFindingType::Sandbox),
            new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiUrlUnknown),
        ], $result->findings);
        static::assertSame(['notice', 'unknown'], \array_map(static fn (ExpressCallbackFinding $finding) => $finding->severity->value, $result->findings));
    }

    public function testTheSandboxComesFromTheCredentialsUtil(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);

        $credentialsUtil = $this->createMock(CredentialsUtilInterface::class);
        $credentialsUtil->expects($this->once())->method('isSandbox')->with($this->salesChannelId)->willReturn(true);

        // the setting says live, as in a SaaS shop that forces the sandbox for test orders
        $result = $this->createDiagnostics($salesChannel, [...self::ACTIVE_SETTINGS, Settings::SANDBOX => false], credentialsUtil: $credentialsUtil)
            ->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertEquals([new ExpressCallbackFinding(ExpressCallbackFindingType::Sandbox)], $result->findings);
    }

    public function testHeadlessSalesChannelsWithAStoreApiUrlOnAnotherHostAreNotTested(): void
    {
        $salesChannel = self::createSalesChannel(typeId: Defaults::SALES_CHANNEL_TYPE_API);
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS, storeApiUrlKnown: true, trustedHost: false)
            ->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertEquals([new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiHostUnknown, 'api.shop.example')], $result->findings);
        static::assertSame(Severity::Unknown, $result->findings[0]->severity);
    }

    public function testHeadlessSalesChannelsWithRecordedStoreApiUrlHaveNoFinding(): void
    {
        $salesChannel = self::createSalesChannel(typeId: Defaults::SALES_CHANNEL_TYPE_API);
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS, storeApiUrlKnown: true)->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertSame([], $result->findings);
    }

    public function testStorefrontsNeedNoStoreApiUrl(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);

        $result = $this->createDiagnostics($salesChannel, self::ACTIVE_SETTINGS)->check($this->salesChannelId, Context::createDefaultContext(), false);

        static::assertSame([], $result->findings);
    }

    public function testNoFindingsUnlessActive(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);

        $diagnostics = $this->createDiagnostics($salesChannel, [...self::ACTIVE_SETTINGS, Settings::IS_LOCAL_ENVIRONMENT => true], CallbackOutcome::Answered);
        $result = $diagnostics->check($this->salesChannelId, Context::createDefaultContext(), true);

        static::assertSame([], $result->findings);
        static::assertSame(CallbackOutcome::Answered, $result->lastCallback?->outcome);
    }

    public function testTestsTheReachabilityOfTheSalesChannel(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);
        $results = [new ReachabilityResult('https://shop.example/paypal/express/shipping-callback/<token>', null, ['93.184.216.34'], 400)];

        $token = null;
        $urls = ['https://shop.example/paypal/express/shipping-callback'];
        $urlBuilder = $this->createMock(CallbackUrlBuilder::class);
        $urlBuilder->expects($this->once())
            ->method('getCallbackUrls')
            ->with($salesChannel, static::callback(static function (string $argument) use (&$token) {
                $token = $argument;

                return \preg_match('/^[0-9a-f]{32}$/', $argument) === 1;
            }), static::isInstanceOf(Context::class))
            ->willReturn($urls);

        $probe = $this->createMock(ReachabilityProbe::class);
        $probe->expects($this->once())->method('probe')->with($urls, static::callback(static function (string $argument) use (&$token) {
            return $argument === $token;
        }))->willReturn($results);

        $diagnostics = $this->createDiagnostics($salesChannel, [], probe: $probe, urlBuilder: $urlBuilder);

        static::assertSame($results, $diagnostics->testReachability($this->salesChannelId, Context::createDefaultContext()));
    }

    public function testRecognizesTheAnswerOfTheShippingCallbackRoute(): void
    {
        $salesChannel = self::createSalesChannel();
        $salesChannel->setId($this->salesChannelId);
        $urlBuilder = $this->createMock(CallbackUrlBuilder::class);
        $urlBuilder->method('getCallbackUrls')->willReturn(['https://shop.example/paypal/express/shipping-callback/a/b', 'https://other.example/paypal/express/shipping-callback/a/b']);

        $httpClient = new MockHttpClient([
            // the route answers a test request with an error status and its header
            new MockResponse('{"error":"Invalid payload"}', ['http_code' => 400, 'response_headers' => [ReachabilityProbe::HEADER . ': 1']]),
            // another server may answer the same text, but not with the header
            new MockResponse('{"error":"Invalid payload"}', ['http_code' => 400]),
        ]);
        // the probe lists the errors first
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['93.184.216.34']));

        $results = $this->createDiagnostics($salesChannel, [], probe: $probe, urlBuilder: $urlBuilder)
            ->testReachability($this->salesChannelId, Context::createDefaultContext());

        static::assertSame([ReachabilityError::UnexpectedAnswer, null], \array_map(static fn (ReachabilityResult $result) => $result->error, $results));
    }

    public function testThrowsForAnUnknownSalesChannel(): void
    {
        $diagnostics = new ExpressCallbackDiagnostics(
            new StaticEntityRepository([new SalesChannelCollection()]),
            new StaticSystemConfigService(),
            $this->createPaymentMethodUtil(),
            new LastCallbackStorage(new KeyValueStorageMock(), new MockClock(), new NullLogger()),
            $this->createMock(ReachabilityProbe::class),
            $this->createMock(CallbackUrlBuilder::class),
            new StoreApiUrlStorage(new KeyValueStorageMock(), new NullLogger()),
            $this->createMock(TrustedHosts::class),
            $this->createMock(CredentialsUtilInterface::class),
        );

        $this->expectExceptionObject(SalesChannelException::salesChannelNotFound($this->salesChannelId));

        $diagnostics->check($this->salesChannelId, Context::createDefaultContext(), false);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createDiagnostics(
        SalesChannelEntity $salesChannel,
        array $settings,
        ?CallbackOutcome $lastCallback = null,
        bool $storeApiUrlKnown = false,
        bool $sandbox = false,
        bool $trustedHost = true,
        ?CredentialsUtilInterface $credentialsUtil = null,
        ?ReachabilityProbe $probe = null,
        ?CallbackUrlBuilder $urlBuilder = null,
    ): ExpressCallbackDiagnostics {
        $storage = new LastCallbackStorage(new KeyValueStorageMock(), new MockClock(), new NullLogger());
        if ($lastCallback !== null) {
            $storage->record($salesChannel->getId(), $lastCallback);
        }

        $storeApiUrlStorage = new StoreApiUrlStorage(new KeyValueStorageMock(), new NullLogger());
        if ($storeApiUrlKnown) {
            $storeApiUrlStorage->record($salesChannel->getId(), 'https://api.shop.example/store-api/paypal/express/shipping-callback/a/b');
        }

        $trustedHosts = $this->createMock(TrustedHosts::class);
        $trustedHosts->method('contains')->willReturn($trustedHost);

        if ($credentialsUtil === null) {
            $credentialsUtil = static::createStub(CredentialsUtilInterface::class);
            $credentialsUtil->method('isSandbox')->willReturn($sandbox);
        }

        return new ExpressCallbackDiagnostics(
            new StaticEntityRepository([new SalesChannelCollection([$salesChannel])]),
            new StaticSystemConfigService($settings),
            $this->createPaymentMethodUtil(),
            $storage,
            $probe ?? $this->createMock(ReachabilityProbe::class),
            $urlBuilder ?? $this->createMock(CallbackUrlBuilder::class),
            $storeApiUrlStorage,
            $trustedHosts,
            $credentialsUtil,
        );
    }

    private function createPaymentMethodUtil(): PaymentMethodUtil
    {
        $paymentMethodUtil = static::createStub(PaymentMethodUtil::class);
        $paymentMethodUtil->method('getPaymentMethodId')->willReturnMap([[PayPalMethodData::class, self::PAYPAL_PAYMENT_METHOD_ID]]);

        return $paymentMethodUtil;
    }

    /**
     * @param bool|null $payPal null when PayPal isn't assigned to the Sales Channel, else whether it is active
     * @param list<string> $domains
     */
    private static function createSalesChannel(
        ?bool $payPal = true,
        string $typeId = Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
        array $domains = ['https://shop.example'],
    ): SalesChannelEntity {
        $paymentMethods = new PaymentMethodCollection();
        if ($payPal !== null) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId(self::PAYPAL_PAYMENT_METHOD_ID);
            $paymentMethod->setActive($payPal);
            $paymentMethods->add($paymentMethod);
        }

        $domainCollection = new SalesChannelDomainCollection();
        foreach ($domains as $url) {
            $domain = new SalesChannelDomainEntity();
            $domain->setId(Uuid::randomHex());
            $domain->setUrl($url);
            $domainCollection->add($domain);
        }

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $salesChannel->setTypeId($typeId);
        $salesChannel->setPaymentMethods($paymentMethods);
        $salesChannel->setDomains($domainCollection);

        return $salesChannel;
    }
}

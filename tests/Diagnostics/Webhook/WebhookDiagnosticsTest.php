<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook;

use GuzzleHttp\Psr7\Response;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\RequestService;
use Shopware\PayPalSDK\Test\Gateway\TestGateways;
use Shopware\PayPalSDK\Test\Request\TestClient;
use Shopware\PayPalSDK\Test\Request\TestRequestContext;
use Swag\PayPal\Diagnostics\DiagnosticsException;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\MissedEvents;
use Swag\PayPal\Diagnostics\Webhook\MissedEventsChecker;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Diagnostics\Webhook\WebhookCandidateUrl;
use Swag\PayPal\Diagnostics\Webhook\WebhookDiagnostics;
use Swag\PayPal\Diagnostics\Webhook\WebhookFinding;
use Swag\PayPal\Diagnostics\Webhook\WebhookFindingType;
use Swag\PayPal\Diagnostics\Webhook\WebhookTestTarget;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Registration\WebhookRegistration;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\Registration\WebhookUrlSource;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookDiagnostics::class)]
#[CoversClass(DiagnosticsException::class)]
class WebhookDiagnosticsTest extends TestCase
{
    use PayPalLogTrait;

    private const EXECUTE_TOKEN = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const WEBHOOK_ID = '5GP028458E2496506';

    private const LAST_EVENT_ID = 'WH-LAST';

    private const REGISTERED_URL = 'https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=' . self::EXECUTE_TOKEN;

    private string $salesChannelId;

    private TestClient $client;

    private MockClock $clock;

    private KeyValueStorageMock $keyValueStorage;

    private TestWebhookStorage $testWebhookStorage;

    private LastWebhookStorage $lastWebhookStorage;

    protected function setUp(): void
    {
        $this->salesChannelId = Uuid::randomHex();
        $this->client = new TestClient();
        $this->clock = new MockClock('2026-11-12 10:00:00', 'UTC');
        $this->keyValueStorage = new KeyValueStorageMock();
        $this->testWebhookStorage = new TestWebhookStorage($this->keyValueStorage, new NullLogger());
        $this->lastWebhookStorage = new LastWebhookStorage($this->keyValueStorage, $this->clock, new NullLogger(), $this->testWebhookStorage);
    }

    /**
     * @param list<WebhookFindingType> $findings
     */
    #[DataProvider('registrationProvider')]
    public function testRegistration(WebhookRegistrationStatus $status, array $findings, bool $hasUrls): void
    {
        $result = $this->createDiagnostics($this->createChecker($status), appUrl: 'https://app.example')
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertSame($status, $result->registration);
        static::assertEquals(\array_map(static fn (WebhookFindingType $type) => new WebhookFinding($type), $findings), $result->findings);
        static::assertSame($hasUrls ? 'https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=<token>' : null, $result->registeredUrl);
        static::assertEquals(
            $hasUrls ? [
                new WebhookCandidateUrl(WebhookUrlSource::Administration, 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>'),
                new WebhookCandidateUrl(WebhookUrlSource::AppUrl, 'https://app.example/api/_action/paypal/webhook/execute?sw-token=<token>'),
            ] : [],
            $result->candidateUrls,
        );
        static::assertNull($result->missedEvents);
    }

    /**
     * @return iterable<string, array{WebhookRegistrationStatus, list<WebhookFindingType>, bool}>
     */
    public static function registrationProvider(): iterable
    {
        yield 'valid' => [WebhookRegistrationStatus::Valid, [], false];
        yield 'registered for another URL' => [WebhookRegistrationStatus::Invalid, [WebhookFindingType::RegistrationInvalid], true];
        yield 'missing' => [WebhookRegistrationStatus::Missing, [WebhookFindingType::RegistrationMissing], false];
        yield 'local development environment' => [WebhookRegistrationStatus::Disabled, [], false];
    }

    public function testRegisteredWithAnOutdatedToken(): void
    {
        $checker = $this->createChecker(WebhookRegistrationStatus::Invalid, outdated: true);

        $result = $this->createDiagnostics($checker)->check($this->salesChannelId, Context::createDefaultContext());

        static::assertSame(WebhookRegistrationStatus::Invalid, $result->registration);
        static::assertEquals([new WebhookFinding(WebhookFindingType::RegistrationOutdated)], $result->findings);
        static::assertSame('https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=<token>', $result->registeredUrl);
        static::assertCount(1, $result->candidateUrls);
    }

    public function testRegistrationIsUnknownWhenPayPalCantBeAsked(): void
    {
        $checker = $this->createMock(WebhookRegistrationChecker::class);
        $checker->method('check')->willThrowException(new \RuntimeException('Invalid credentials'));

        $result = $this->createDiagnostics($checker, logger: $this->createPayPalLogger())->check($this->salesChannelId, Context::createDefaultContext());

        static::assertSame(WebhookRegistrationStatus::Unknown, $result->registration);
        static::assertEquals([new WebhookFinding(WebhookFindingType::RegistrationUnknown)], $result->findings);
        static::assertNull($result->missedEvents);
        $record = $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_WEBHOOK_REGISTRATION_UNCHECKED,
            Level::Warning,
            [Log::SALES_CHANNEL_ID => $this->salesChannelId],
            'Could not check the webhook registration at PayPal, the diagnostics show it as unknown',
        );
        static::assertSame('Invalid credentials', $record->context[Log::EXCEPTION]['message'] ?? null);
    }

    public function testFindingsMostSevereFirst(): void
    {
        $checker = $this->createMock(WebhookRegistrationChecker::class);
        $checker->method('check')->willThrowException(new \RuntimeException('Invalid credentials'));

        $result = $this->createDiagnostics($checker, WebhookOutcome::UnknownTransaction)->check($this->salesChannelId, Context::createDefaultContext());

        static::assertEquals([
            new WebhookFinding(WebhookFindingType::UnknownTransaction),
            new WebhookFinding(WebhookFindingType::RegistrationUnknown),
        ], $result->findings);
        static::assertSame(['warning', 'unknown'], \array_map(static fn (WebhookFinding $finding) => $finding->severity->value, $result->findings));

        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Invalid), WebhookOutcome::Failed)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertEquals([
            new WebhookFinding(WebhookFindingType::LastWebhookFailed),
            new WebhookFinding(WebhookFindingType::RegistrationInvalid),
        ], $result->findings);
    }

    #[DataProvider('outcomeProvider')]
    public function testFindingsOfTheLastWebhook(WebhookOutcome $outcome, ?WebhookFindingType $type): void
    {
        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), $outcome)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertEquals($type === null ? [] : [new WebhookFinding($type)], $result->findings);
        static::assertSame($outcome, $result->lastWebhook?->outcome);
        static::assertSame('PAYMENT.CAPTURE.COMPLETED', $result->lastWebhook->eventType);
        static::assertSame(self::LAST_EVENT_ID, $result->lastWebhook->eventId);
    }

    public static function outcomeProvider(): \Generator
    {
        yield 'handled' => [WebhookOutcome::Handled, null];
        yield 'unknown payment' => [WebhookOutcome::UnknownTransaction, WebhookFindingType::UnknownTransaction];
        yield 'failed' => [WebhookOutcome::Failed, WebhookFindingType::LastWebhookFailed];
    }

    public function testNoLastWebhookWithoutToken(): void
    {
        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), settings: [])
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertNull($result->lastWebhook);
        static::assertSame([], $result->findings);
    }

    public function testNoFindingsWhenDisabled(): void
    {
        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Disabled), WebhookOutcome::Failed)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertSame([], $result->findings);
        static::assertSame(WebhookOutcome::Failed, $result->lastWebhook?->outcome);
    }

    public function testMissedEventsOfAValidRegistration(): void
    {
        $this->client->addResponse($this->createJsonResponse(200, ['events' => [
            ['id' => 'WH-2', 'create_time' => '2026-11-12T09:40:00.000Z', 'event_type' => 'PAYMENT.CAPTURE.REFUNDED'],
        ]]));

        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), WebhookOutcome::Handled)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertEquals(new MissedEvents(1, false, '2026-11-12T09:40:00.000Z', 'PAYMENT.CAPTURE.REFUNDED'), $result->missedEvents);
        static::assertEquals([new WebhookFinding(WebhookFindingType::MissedEvents)], $result->findings);
    }

    public function testPayPalIsNotAskedForMissedEventsWithoutALastWebhook(): void
    {
        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid))
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertNull($result->missedEvents);
        static::assertNull($this->client->getLast());
    }

    #[DataProvider('statusWithoutMissedEventsProvider')]
    public function testPayPalIsOnlyAskedForMissedEventsOfAValidRegistration(WebhookRegistrationStatus $status): void
    {
        $result = $this->createDiagnostics($this->createChecker($status), WebhookOutcome::Handled)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertNull($result->missedEvents);
        static::assertNull($this->client->getLast());
    }

    public static function statusWithoutMissedEventsProvider(): \Generator
    {
        yield 'registered for another URL' => [WebhookRegistrationStatus::Invalid];
        yield 'missing' => [WebhookRegistrationStatus::Missing];
        yield 'disabled' => [WebhookRegistrationStatus::Disabled];
    }

    public function testPayPalIsAskedOnceForTheRegistration(): void
    {
        $checker = $this->createMock(WebhookRegistrationChecker::class);
        $checker->expects($this->once())->method('check')->willReturn(new WebhookRegistration(WebhookRegistrationStatus::Invalid, self::REGISTERED_URL));

        $this->createDiagnostics($checker)->check($this->salesChannelId, Context::createDefaultContext());
    }

    /**
     * @param list<string> $sources
     */
    #[DataProvider('candidateProvider')]
    public function testCandidateUrlsOfAnInvalidRegistration(string $appUrl, array $sources): void
    {
        $result = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Invalid), appUrl: $appUrl)
            ->check($this->salesChannelId, Context::createDefaultContext());

        static::assertSame($sources, \array_map(static fn (WebhookCandidateUrl $candidate) => $candidate->source->value, $result->candidateUrls));
        foreach ($result->candidateUrls as $candidate) {
            static::assertStringEndsWith('?sw-token=<token>', $candidate->url);
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function candidateProvider(): iterable
    {
        yield 'without an APP_URL' => ['', ['administration']];
        yield 'with an APP_URL' => ['https://app.example', ['administration', 'app_url']];
        yield 'with the same host' => ['https://shop.example', ['administration']];
    }

    public function testReachabilityUrlIsHttpsWithAToken(): void
    {
        $router = $this->createRouter('http', 8000);

        $urls = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$urls) {
            $urls[] = $url;

            return new MockResponse('', ['http_code' => 204, 'response_headers' => [ReachabilityProbe::HEADER => '1']]);
        });

        $results = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), router: $router, httpClient: $httpClient)
            ->testReachability($this->salesChannelId, Context::createDefaultContext());

        static::assertCount(1, $urls);
        static::assertMatchesRegularExpression('#^https://shop\.example/api/_action/paypal/webhook/execute\?sw-token=[0-9a-f]{32}$#', $urls[0]);
        static::assertSame('http', $router->getContext()->getScheme());
        static::assertCount(1, $results);
        static::assertSame('https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>', $results[0]->url);
        static::assertNull($results[0]->error);
    }

    public function testReachabilityProbesEveryDistinctUrl(): void
    {
        $urls = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$urls) {
            $urls[] = $url;

            return new MockResponse('', ['http_code' => 204, 'response_headers' => [ReachabilityProbe::HEADER => '1']]);
        });

        $results = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), httpClient: $httpClient, appUrl: 'https://app.example')
            ->testReachability($this->salesChannelId, Context::createDefaultContext());

        static::assertCount(2, $results);
        static::assertCount(2, $urls);
        static::assertStringStartsWith('https://shop.example/', $urls[0]);
        static::assertStringStartsWith('https://app.example/', $urls[1]);
    }

    public function testReachabilityDoesNotRecognizeOtherAnswers(): void
    {
        $results = $this->createDiagnostics(
            $this->createChecker(WebhookRegistrationStatus::Valid),
            httpClient: new MockHttpClient(new MockResponse('Shopware token is invalid', ['http_code' => 400])),
        )->testReachability($this->salesChannelId, Context::createDefaultContext());

        static::assertSame(ReachabilityError::UnexpectedAnswer, $results[0]->error);
    }

    public function testSendTestWebhookToTheRegisteredUrl(): void
    {
        $this->client->addResponse($this->createSimulatedEventResponse());

        $testWebhook = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid))
            ->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext());

        static::assertEquals(new TestWebhook('WH-MOCK', new \DateTimeImmutable('2026-11-12 10:00:00', new \DateTimeZone('UTC'))), $testWebhook);
        static::assertSame(
            ['webhook_id' => self::WEBHOOK_ID, 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource_version' => '2.0'],
            $this->getLastRequest()->getRequestBody(),
        );
    }

    #[DataProvider('provideExpectedRouterUrls')]
    public function testSendTestWebhookToTheAdministrationUrl(string $scheme, int $port, string $expectedHost): void
    {
        $router = $this->createRouter($scheme, $port);
        $this->client->addResponse($this->createSimulatedEventResponse());

        $testWebhook = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Invalid), router: $router)
            ->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Administration, Context::createDefaultContext());

        static::assertSame('WH-MOCK', $testWebhook->eventId);
        static::assertSame(
            [
                'url' => 'https://' . $expectedHost . '/api/_action/paypal/webhook/execute?sw-token=' . self::EXECUTE_TOKEN,
                'event_type' => 'CHECKOUT.ORDER.APPROVED',
                'resource_version' => '2.0',
            ],
            $this->getLastRequest()->getRequestBody(),
        );
        static::assertSame($scheme, $router->getContext()->getScheme());
    }

    /**
     * The plugin registers HTTPS with the HTTPS port, not the port of the request.
     *
     * @return iterable<string, array{string, int, string}>
     */
    public static function provideExpectedRouterUrls(): iterable
    {
        yield 'http on a custom port' => ['http', 8000, 'shop.example'];
        yield 'http on the default port' => ['http', 80, 'shop.example'];
        yield 'https on the default port' => ['https', 443, 'shop.example'];
        yield 'https on a custom port' => ['https', 8443, 'shop.example:8443'];
    }

    public function testSendTestWebhookToTheAppUrl(): void
    {
        $this->client->addResponse($this->createSimulatedEventResponse());

        $testWebhook = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Invalid), appUrl: 'https://app.example')
            ->sendTestWebhook($this->salesChannelId, WebhookTestTarget::AppUrl, Context::createDefaultContext());

        static::assertSame('WH-MOCK', $testWebhook->eventId);
        static::assertSame(
            'https://app.example/api/_action/paypal/webhook/execute?sw-token=' . self::EXECUTE_TOKEN,
            $this->getLastRequest()->getRequestBody()['url'] ?? null,
        );
    }

    public function testSendTestWebhookToTheAppUrlWithoutAppUrl(): void
    {
        try {
            $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Invalid))
                ->sendTestWebhook($this->salesChannelId, WebhookTestTarget::AppUrl, Context::createDefaultContext());
            static::fail('Expected an exception');
        } catch (DiagnosticsException $e) {
            static::assertSame('SWAG_PAYPAL_DIAGNOSTICS__NO_APP_URL', $e->getErrorCode());
            static::assertSame(400, $e->getStatusCode());
        }

        static::assertNull($this->client->getLast());
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('provideMissingSettings')]
    public function testSendTestWebhookWithoutRegistration(WebhookTestTarget $target, array $settings): void
    {
        $diagnostics = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Missing), settings: $settings);

        try {
            $diagnostics->sendTestWebhook($this->salesChannelId, $target, Context::createDefaultContext());
            static::fail('Expected an exception');
        } catch (DiagnosticsException $e) {
            static::assertSame('SWAG_PAYPAL_DIAGNOSTICS__NO_WEBHOOK_REGISTERED', $e->getErrorCode());
            static::assertSame(400, $e->getStatusCode());
        }

        static::assertNull($this->client->getLast());
    }

    /**
     * @return iterable<string, array{WebhookTestTarget, array<string, mixed>}>
     */
    public static function provideMissingSettings(): iterable
    {
        yield 'app URL without an execute token' => [WebhookTestTarget::AppUrl, [Settings::WEBHOOK_ID => self::WEBHOOK_ID]];
        yield 'registered without a webhook ID' => [WebhookTestTarget::Registered, [Settings::WEBHOOK_EXECUTE_TOKEN => self::EXECUTE_TOKEN]];
        yield 'registered without an execute token' => [WebhookTestTarget::Registered, [Settings::WEBHOOK_ID => self::WEBHOOK_ID]];
        yield 'administration without an execute token' => [WebhookTestTarget::Administration, [Settings::WEBHOOK_ID => self::WEBHOOK_ID]];
    }

    public function testSendTestWebhookLetsPayPalErrorsThrough(): void
    {
        $this->client->addResponse($this->createJsonResponse(400, ['name' => 'INVALID_REQUEST', 'message' => 'Bad request']));

        try {
            $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid))
                ->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext());
            static::fail('Expected an exception');
        } catch (ApiException) {
            // the test isn't stored
        }

        static::assertFalse($this->hasArrived());
    }

    public function testTheTestWebhookArrivesWithItsEventId(): void
    {
        $this->client->addResponse($this->createSimulatedEventResponse());
        $diagnostics = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid));

        static::assertFalse($diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));

        $diagnostics->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext());
        static::assertFalse($diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));

        // another webhook doesn't hide it, as the last webhook alone would
        $this->clock->sleep(2);
        $this->lastWebhookStorage->record(self::EXECUTE_TOKEN, 'CHECKOUT.ORDER.APPROVED', WebhookOutcome::Handled, 'WH-MOCK');
        $this->clock->sleep(2);
        $this->lastWebhookStorage->record(self::EXECUTE_TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled, 'WH-OTHER');

        static::assertSame('WH-OTHER', $this->lastWebhookStorage->get(self::EXECUTE_TOKEN)?->eventId);
        static::assertTrue($diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));
    }

    public function testAnEarlierTestWebhookIsNotThisOne(): void
    {
        // PayPal uses the same event ID for every test webhook
        $this->lastWebhookStorage->record(self::EXECUTE_TOKEN, 'CHECKOUT.ORDER.APPROVED', WebhookOutcome::Handled, 'WH-MOCK');
        $this->client->addResponse($this->createSimulatedEventResponse());
        $this->clock->sleep(10);
        $diagnostics = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid));

        $diagnostics->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext());

        static::assertFalse($diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));
    }

    public function testTheTestWebhookMayArriveBeforePayPalAnswers(): void
    {
        $client = new TestClient([], function () {
            // PayPal delivers the event, then answers the request
            $this->lastWebhookStorage->record(self::EXECUTE_TOKEN, 'CHECKOUT.ORDER.APPROVED', WebhookOutcome::Handled, 'WH-MOCK');

            return $this->createSimulatedEventResponse();
        });
        $diagnostics = $this->createDiagnostics($this->createChecker(WebhookRegistrationStatus::Valid), client: $client);

        $diagnostics->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext());

        static::assertTrue($diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));
    }

    public function testNoTestWebhookArrivedWithoutToken(): void
    {
        static::assertFalse($this->createDiagnostics($this->createMock(WebhookRegistrationChecker::class), settings: [])->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()));
    }

    public function testThrowsForAnUnknownSalesChannel(): void
    {
        $diagnostics = $this->createDiagnostics($this->createMock(WebhookRegistrationChecker::class), salesChannelExists: false);

        foreach ([
            fn () => $diagnostics->check($this->salesChannelId, Context::createDefaultContext()),
            fn () => $diagnostics->testReachability($this->salesChannelId, Context::createDefaultContext()),
            fn () => $diagnostics->sendTestWebhook($this->salesChannelId, WebhookTestTarget::Registered, Context::createDefaultContext()),
            fn () => $diagnostics->hasTestWebhookArrived($this->salesChannelId, Context::createDefaultContext()),
        ] as $call) {
            try {
                $call();
                static::fail('Expected an exception');
            } catch (SalesChannelException $e) {
                static::assertSame(SalesChannelException::salesChannelNotFound($this->salesChannelId)->getMessage(), $e->getMessage());
            }
        }
    }

    /**
     * A real router, as the scheme and ports of its context decide the generated URL
     */
    private function createRouter(string $scheme, int $port): Router
    {
        $routes = new RouteCollection();
        $routes->add('api.action.paypal.webhook.execute', new Route('/api/_action/paypal/webhook/execute'));

        return new Router(
            new ClosureLoader(),
            static fn () => $routes,
            [],
            new RequestContext(host: 'shop.example', scheme: $scheme, httpPort: $scheme === 'http' ? $port : 80, httpsPort: $scheme === 'https' ? $port : 443),
        );
    }

    private function createChecker(WebhookRegistrationStatus $status, bool $outdated = false): WebhookRegistrationChecker
    {
        $checker = $this->createMock(WebhookRegistrationChecker::class);
        $checker->method('check')->with($this->salesChannelId)->willReturn(new WebhookRegistration(
            $status,
            $status === WebhookRegistrationStatus::Invalid ? self::REGISTERED_URL : null,
            $outdated,
        ));

        return $checker;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createDiagnostics(
        WebhookRegistrationChecker $registrationChecker,
        ?WebhookOutcome $lastWebhook = null,
        array $settings = [Settings::WEBHOOK_EXECUTE_TOKEN => self::EXECUTE_TOKEN, Settings::WEBHOOK_ID => self::WEBHOOK_ID],
        ?Router $router = null,
        ?MockHttpClient $httpClient = null,
        ?LoggerInterface $logger = null,
        ?TestClient $client = null,
        bool $salesChannelExists = true,
        string $appUrl = '',
    ): WebhookDiagnostics {
        if ($lastWebhook !== null) {
            $this->clock->modify('2026-11-12 09:00:00');
            $this->lastWebhookStorage->record(self::EXECUTE_TOKEN, 'PAYMENT.CAPTURE.COMPLETED', $lastWebhook, self::LAST_EVENT_ID);
            $this->clock->modify('2026-11-12 10:00:00');
        }

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $client ??= $this->client;
        $contextFactory = new ApiContextFactoryMock();
        $gateways = new TestGateways($client);
        $gateways->setCachedToken($contextFactory->getApiContext($this->salesChannelId));
        $gateway = new WebhookEventGateway($client, $gateways->tokenGateway(), new RequestService());

        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository(\array_fill(0, 10, $salesChannelExists ? [$this->salesChannelId] : []));

        return new WebhookDiagnostics(
            $salesChannelRepository,
            $registrationChecker,
            new WebhookUrlBuilder($router ?? $this->createRouter('http', 8000), $requestStack, $appUrl),
            new StaticSystemConfigService($settings),
            $this->lastWebhookStorage,
            $this->testWebhookStorage,
            new ReachabilityProbe($httpClient ?? new MockHttpClient(), new HostResolver(static fn () => ['93.184.216.34'])),
            $logger ?? new NullLogger(),
            $contextFactory,
            $gateway,
            $this->clock,
            new MissedEventsChecker($gateway, $contextFactory, $this->clock, new NullLogger()),
        );
    }

    private function hasArrived(): bool
    {
        return $this->testWebhookStorage->hasArrived(self::EXECUTE_TOKEN);
    }

    private function getLastRequest(): TestRequestContext
    {
        $last = $this->client->getLast();
        static::assertNotNull($last);

        return $last;
    }

    private function createSimulatedEventResponse(): Response
    {
        return $this->createJsonResponse(202, [
            'id' => 'WH-MOCK',
            'create_time' => '2026-11-12T10:00:00.000Z',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
        ]);
    }

    /**
     * @param array<mixed> $body
     */
    private function createJsonResponse(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], \json_encode($body, \JSON_THROW_ON_ERROR));
    }
}

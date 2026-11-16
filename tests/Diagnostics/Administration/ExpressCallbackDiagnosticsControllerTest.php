<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Administration;

use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Diagnostics\Administration\ExpressCallbackDiagnosticsController;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackDiagnostics;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFinding;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFindingType;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackResult;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackStatus;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\CallbackOutcome;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallback;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Severity;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressCallbackDiagnosticsController::class)]
class ExpressCallbackDiagnosticsControllerTest extends TestCase
{
    public function testReturnsTheResultOfTheCheck(): void
    {
        $diagnostics = $this->createMock(ExpressCallbackDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('check')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext(), false)
            ->willReturn(new ExpressCallbackResult(
                ExpressCallbackStatus::Active,
                new LastCallback(new \DateTimeImmutable('2026-11-12T10:00:00+00:00'), CallbackOutcome::Answered),
                [new ExpressCallbackFinding(ExpressCallbackFindingType::UntrustedProxy), new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiHostUnknown, 'api.shop.example')],
            ));

        $response = (new ExpressCallbackDiagnosticsController($diagnostics))
            ->check(TestDefaults::SALES_CHANNEL, Request::create('https://shop.example/api'), Context::createDefaultContext());

        static::assertEquals([
            'status' => 'active',
            'lastCallback' => ['at' => '2026-11-12T10:00:00.000+00:00', 'outcome' => 'answered', 'extensions' => []],
            'findings' => [
                ['type' => 'untrusted_proxy', 'severity' => 'warning', 'host' => null, 'extensions' => []],
                ['type' => 'store_api_host_unknown', 'severity' => 'unknown', 'host' => 'api.shop.example', 'extensions' => []],
            ],
            'extensions' => [],
        ], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    #[DataProvider('proxyProvider')]
    public function testDetectsAnUntrustedHttpsProxy(string $url, ?string $forwardedProto, bool $untrusted): void
    {
        $request = Request::create($url, server: ['REMOTE_ADDR' => '203.0.113.10']);
        if ($forwardedProto !== null) {
            $request->headers->set('X-Forwarded-Proto', $forwardedProto);
        }

        $diagnostics = $this->createMock(ExpressCallbackDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('check')
            ->with(TestDefaults::SALES_CHANNEL, static::anything(), $untrusted)
            ->willReturn(new ExpressCallbackResult(ExpressCallbackStatus::Active, null, []));

        (new ExpressCallbackDiagnosticsController($diagnostics))->check(TestDefaults::SALES_CHANNEL, $request, Context::createDefaultContext());
    }

    public static function proxyProvider(): \Generator
    {
        yield 'plain HTTP without proxy' => ['http://shop.example/api', null, false];
        yield 'HTTPS without proxy' => ['https://shop.example/api', null, false];
        yield 'untrusted HTTPS proxy, Symfony sees plain HTTP' => ['http://shop.example/api', 'https', true];
        yield 'trusted HTTPS proxy, Symfony sees HTTPS' => ['https://shop.example/api', 'https', false];
        yield 'HTTP proxy' => ['http://shop.example/api', 'http', false];
    }

    public function testReturnsTheReachabilityOfEachUrl(): void
    {
        $diagnostics = $this->createMock(ExpressCallbackDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('testReachability')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())
            ->willReturn([new ReachabilityResult('https://shop.example/paypal/express/shipping-callback', ReachabilityError::Redirected, ['93.184.216.34'], 302, 'https://shop.example/maintenance')]);

        $response = (new ExpressCallbackDiagnosticsController($diagnostics))->testReachability(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertEquals([[
            'url' => 'https://shop.example/paypal/express/shipping-callback',
            'error' => 'redirected',
            'severity' => 'error',
            'addresses' => ['93.184.216.34'],
            'statusCode' => 302,
            'location' => 'https://shop.example/maintenance',
            'detail' => null,
            'extensions' => [],
        ]], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testSchemaListsEveryEnumValue(): void
    {
        $openApi = (new Generator(new NullLogger()))->generate([__DIR__ . '/../../../src/Diagnostics'], validate: false);
        static::assertInstanceOf(OpenApi::class, $openApi);

        $schemas = \json_decode($openApi->toJson(), true, flags: \JSON_THROW_ON_ERROR)['components']['schemas'];

        static::assertSame(\array_column(ExpressCallbackStatus::cases(), 'value'), $schemas['swag_paypal_diagnostics_express_callback_result']['properties']['status']['enum']);
        static::assertSame(\array_column(ExpressCallbackFindingType::cases(), 'value'), $schemas['swag_paypal_diagnostics_express_callback_finding']['properties']['type']['enum']);
        static::assertSame(\array_column(Severity::cases(), 'value'), $schemas['swag_paypal_diagnostics_express_callback_finding']['properties']['severity']['enum']);
        static::assertSame(\array_column(CallbackOutcome::cases(), 'value'), $schemas['swag_paypal_diagnostics_express_callback_last_callback']['properties']['outcome']['enum']);
        static::assertSame(\array_column(ReachabilityError::cases(), 'value'), $schemas['swag_paypal_diagnostics_reachability']['properties']['error']['enum']);
    }
}

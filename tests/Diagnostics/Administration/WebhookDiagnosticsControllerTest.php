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
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Diagnostics\Administration\WebhookDiagnosticsController;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Severity;
use Swag\PayPal\Diagnostics\Webhook\MissedEvents;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Diagnostics\Webhook\WebhookCandidateUrl;
use Swag\PayPal\Diagnostics\Webhook\WebhookDiagnostics;
use Swag\PayPal\Diagnostics\Webhook\WebhookFinding;
use Swag\PayPal\Diagnostics\Webhook\WebhookFindingType;
use Swag\PayPal\Diagnostics\Webhook\WebhookResult;
use Swag\PayPal\Diagnostics\Webhook\WebhookTestTarget;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;
use Swag\PayPal\Webhook\Registration\WebhookUrlSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookDiagnosticsController::class)]
#[CoversClass(TestWebhook::class)]
class WebhookDiagnosticsControllerTest extends TestCase
{
    public function testReturnsTheResultOfTheCheck(): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('check')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())
            ->willReturn(new WebhookResult(
                WebhookRegistrationStatus::Invalid,
                new LastWebhook(new \DateTimeImmutable('2026-11-12T10:00:00+00:00'), 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Failed, 'WH-1'),
                [new WebhookFinding(WebhookFindingType::RegistrationInvalid), new WebhookFinding(WebhookFindingType::LastWebhookFailed)],
                'https://old.example/webhook?sw-token=<token>',
                [new WebhookCandidateUrl(WebhookUrlSource::Administration, 'https://shop.example/webhook?sw-token=<token>'), new WebhookCandidateUrl(WebhookUrlSource::AppUrl, 'https://app.example/webhook?sw-token=<token>')],
                new MissedEvents(3, true, '2026-11-12T10:30:00.000Z', 'PAYMENT.CAPTURE.REFUNDED'),
            ));

        $response = (new WebhookDiagnosticsController($diagnostics))->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertEquals([
            'registration' => 'invalid',
            'lastWebhook' => ['at' => '2026-11-12T10:00:00.000+00:00', 'eventType' => 'PAYMENT.CAPTURE.COMPLETED', 'outcome' => 'failed', 'eventId' => 'WH-1', 'extensions' => []],
            'registeredUrl' => 'https://old.example/webhook?sw-token=<token>',
            'candidateUrls' => [
                ['source' => 'administration', 'url' => 'https://shop.example/webhook?sw-token=<token>', 'extensions' => []],
                ['source' => 'app_url', 'url' => 'https://app.example/webhook?sw-token=<token>', 'extensions' => []],
            ],
            'missedEvents' => ['count' => 3, 'more' => true, 'newestAt' => '2026-11-12T10:30:00.000Z', 'newestEventType' => 'PAYMENT.CAPTURE.REFUNDED', 'extensions' => []],
            'findings' => [
                ['type' => 'registration_invalid', 'severity' => 'warning', 'extensions' => []],
                ['type' => 'last_webhook_failed', 'severity' => 'error', 'extensions' => []],
            ],
            'extensions' => [],
        ], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testReturnsTheReachabilityOfTheWebhookUrl(): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('testReachability')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())
            ->willReturn([new ReachabilityResult('https://shop.example/api/_action/paypal/webhook/execute', ReachabilityError::Denied, ['93.184.216.34'], 403)]);

        $response = (new WebhookDiagnosticsController($diagnostics))->testReachability(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertEquals([[
            'url' => 'https://shop.example/api/_action/paypal/webhook/execute',
            'error' => 'denied',
            'severity' => 'error',
            'addresses' => ['93.184.216.34'],
            'statusCode' => 403,
            'location' => null,
            'detail' => null,
            'extensions' => [],
        ]], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    #[DataProvider('provideTargets')]
    public function testSendsATestWebhook(string $value, WebhookTestTarget $target): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('sendTestWebhook')
            ->with(TestDefaults::SALES_CHANNEL, $target, Context::createDefaultContext())
            ->willReturn(new TestWebhook('WH-MOCK-1', new \DateTimeImmutable('2026-11-12T10:00:00+00:00')));

        $response = (new WebhookDiagnosticsController($diagnostics))->sendTestWebhook(TestDefaults::SALES_CHANNEL, new Request(request: ['target' => $value]), Context::createDefaultContext());

        static::assertEquals(['eventId' => 'WH-MOCK-1', 'sentAt' => '2026-11-12T10:00:00.000+00:00', 'extensions' => []], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    /**
     * @return iterable<string, array{string, WebhookTestTarget}>
     */
    public static function provideTargets(): iterable
    {
        yield 'registered' => ['registered', WebhookTestTarget::Registered];
        yield 'administration' => ['administration', WebhookTestTarget::Administration];
        yield 'app_url' => ['app_url', WebhookTestTarget::AppUrl];
    }

    public function testSendingATestWebhookRequiresATarget(): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->never())->method('sendTestWebhook');

        $this->expectExceptionObject(RoutingException::missingRequestParameter('target'));

        (new WebhookDiagnosticsController($diagnostics))->sendTestWebhook(TestDefaults::SALES_CHANNEL, new Request(), Context::createDefaultContext());
    }

    public function testSendingATestWebhookRejectsAnUnknownTarget(): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->never())->method('sendTestWebhook');

        $this->expectExceptionObject(RoutingException::invalidRequestParameter('target'));

        (new WebhookDiagnosticsController($diagnostics))->sendTestWebhook(TestDefaults::SALES_CHANNEL, new Request(request: ['target' => 'elsewhere']), Context::createDefaultContext());
    }

    #[DataProvider('provideArrivals')]
    public function testReturnsWhetherTheTestWebhookArrived(bool $arrived): void
    {
        $diagnostics = $this->createMock(WebhookDiagnostics::class);
        $diagnostics->expects($this->once())
            ->method('hasTestWebhookArrived')
            ->with(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())
            ->willReturn($arrived);

        $response = (new WebhookDiagnosticsController($diagnostics))->getTestWebhookArrival(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertSame(['arrived' => $arrived], \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideArrivals(): iterable
    {
        yield 'arrived' => [true];
        yield 'not arrived' => [false];
    }

    public function testSchemaListsEveryEnumValue(): void
    {
        $openApi = (new Generator(new NullLogger()))->generate([__DIR__ . '/../../../src/Diagnostics'], validate: false);
        static::assertInstanceOf(OpenApi::class, $openApi);

        $decoded = \json_decode($openApi->toJson(), true, flags: \JSON_THROW_ON_ERROR);
        $schemas = $decoded['components']['schemas'];
        $paths = $decoded['paths'];

        static::assertSame(\array_column(WebhookRegistrationStatus::cases(), 'value'), $schemas['swag_paypal_diagnostics_webhook_result']['properties']['registration']['enum']);
        static::assertArrayNotHasKey('registrationScope', $schemas['swag_paypal_diagnostics_webhook_result']['properties']);
        static::assertSame(\array_column(WebhookUrlSource::cases(), 'value'), $schemas['swag_paypal_diagnostics_webhook_candidate_url']['properties']['source']['enum']);
        static::assertSame(\array_column(WebhookFindingType::cases(), 'value'), $schemas['swag_paypal_diagnostics_webhook_finding']['properties']['type']['enum']);
        static::assertSame(\array_column(Severity::cases(), 'value'), $schemas['swag_paypal_diagnostics_webhook_finding']['properties']['severity']['enum']);
        static::assertSame(['count', 'more', 'newestAt', 'newestEventType'], $schemas['swag_paypal_diagnostics_webhook_missed_events']['required']);
        $testRequest = $paths['/_action/paypal/diagnostics/webhook/{salesChannelId}/test']['post']['requestBody']['content']['application/json']['schema'];
        static::assertSame(['target'], $testRequest['required']);
        static::assertSame(\array_column(WebhookTestTarget::cases(), 'value'), $testRequest['properties']['target']['enum']);
        static::assertSame(\array_column(WebhookOutcome::cases(), 'value'), $schemas['swag_paypal_diagnostics_webhook_last_webhook']['properties']['outcome']['enum']);
        static::assertSame(['arrived'], $paths['/_action/paypal/diagnostics/webhook/{salesChannelId}/test']['get']['responses']['200']['content']['application/json']['schema']['required']);
    }
}

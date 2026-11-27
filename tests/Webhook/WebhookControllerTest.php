<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Test\Webhook\_fixtures\WebhookDataFixture;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    use PayPalLogTrait;

    public const THROW_PAYPAL_API_EXCEPTION = 'executeWebhookThrowsPayPalApiException';
    public const THROW_WEBHOOK_EXCEPTION = 'executeWebhookThrowsWebhookException';
    public const THROW_GENERAL_EXCEPTION = 'executeWebhookThrowsGeneralException';
    public const THROW_NOT_FOUND_EXCEPTION = 'executeWebhookThrowsNotFoundException';
    public const THROW_TRANSACTION_UNKNOWN_EXCEPTION = 'executeWebhookThrowsTransactionUnknownException';
    public const EMPTY_TOKEN = 'emptyExecuteToken';

    private WebhookController $controller;

    protected function setUp(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], $this->createMock(ContainerInterface::class));
        $systemConfigRepo = $definitionRegistry->getRepository(
            (new SystemConfigDefinition())->getEntityName()
        );

        $this->controller = new WebhookController(
            $this->createPayPalLogger(),
            new WebhookServiceMock(SystemConfigServiceMock::createWithoutCredentials()),
            $systemConfigRepo,
            new WebhookUrlBuilder($this->createMock(Router::class), new RequestStack(), ''),
            new RequestStack(),
        );
    }

    public function testStatusWebhook(): void
    {
        $jsonResponse = $this->controller->statusWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        static::assertSame(['result' => WebhookService::STATUS_WEBHOOK_VALID, 'urlSources' => []], $result);
    }

    /**
     * @param list<array{source: string, host: string}> $expected
     */
    #[DataProvider('provideUrlSourcesOfTheStatus')]
    public function testStatusWebhookListsTheHostsToRegisterFor(string $appUrl, array $expected): void
    {
        [$controller] = $this->createRegisteringController($appUrl);

        $result = \json_decode((string) $controller->statusWebhook(TestDefaults::SALES_CHANNEL)->getContent(), true);

        static::assertSame($expected, $result['urlSources']);
    }

    /**
     * @return iterable<string, array{string, list<array{source: string, host: string}>}>
     */
    public static function provideUrlSourcesOfTheStatus(): iterable
    {
        yield 'without an APP_URL' => ['', [['source' => 'administration', 'host' => 'shop.example']]];
        yield 'with another APP_URL' => ['https://app.example', [
            ['source' => 'administration', 'host' => 'shop.example'],
            ['source' => 'app_url', 'host' => 'app.example'],
        ]];
        yield 'with the APP_URL of the Administration' => ['https://shop.example', [['source' => 'administration', 'host' => 'shop.example']]];
        yield 'with an APP_URL with a port' => ['https://app.example:8443', [
            ['source' => 'administration', 'host' => 'shop.example'],
            ['source' => 'app_url', 'host' => 'app.example:8443'],
        ]];
    }

    public function testStatusWebhookNeverExposesTheExecuteToken(): void
    {
        [$controller] = $this->createRegisteringController('https://app.example');

        static::assertStringNotContainsString('sw-token', (string) $controller->statusWebhook(TestDefaults::SALES_CHANNEL)->getContent());
    }

    public function testRegisterWebhook(): void
    {
        $jsonResponse = $this->controller->registerWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        static::assertSame(['result' => WebhookService::WEBHOOK_CREATED], $result);
    }

    #[DataProvider('provideUrlSources')]
    public function testRegisterWebhookWithAUrlSource(?string $urlSource, string $expectedUrl): void
    {
        [$controller] = $this->createRegisteringController('https://app.example', new Request([], $urlSource === null ? [] : ['urlSource' => $urlSource]));
        $result = \json_decode((string) $controller->registerWebhook(TestDefaults::SALES_CHANNEL)->getContent(), true);

        static::assertSame(['result' => $expectedUrl], $result);
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function provideUrlSources(): iterable
    {
        yield 'default' => [null, 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'administration' => ['administration', 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=t'];
        yield 'app_url' => ['app_url', 'https://app.example/api/_action/paypal/webhook/execute?sw-token=t'];
    }

    public function testRegisterWebhookRejectsAnUnknownUrlSource(): void
    {
        [$controller] = $this->createRegisteringController('https://app.example', new Request([], ['urlSource' => 'other']));

        $this->expectException(RoutingException::class);

        $controller->registerWebhook(TestDefaults::SALES_CHANNEL);
    }

    public function testRegisterWebhookRejectsTheAppUrlWithoutAppUrl(): void
    {
        [$controller] = $this->createRegisteringController('', new Request([], ['urlSource' => 'app_url']));

        $this->expectException(RoutingException::class);

        $controller->registerWebhook(TestDefaults::SALES_CHANNEL);
    }

    public function testDeregisterWebhook(): void
    {
        $jsonResponse = $this->controller->deregisterWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        // no action because no Webhook ID is defined by default
        static::assertSame(['result' => WebhookService::NO_WEBHOOK_ACTION_REQUIRED], $result);
    }

    public function testExecuteWebhook(): void
    {
        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->assertPayPalLogged(
            LogCode::WEBHOOK_EVENT_PROCESSED,
            Level::Info,
            message: 'Processed PayPal webhook event {webhookEventType}',
        );
        static::assertArrayNotHasKey(Log::PAYLOAD, $record->context);
        static::assertArrayNotHasKey('webhook', $record->context);
    }

    public function testExecuteWebhookThrowsPayPalApiException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_PAYPAL_API_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('An error occurred during execution of webhook', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $record = $this->assertPayPalLogged(LogCode::WEBHOOK_EVENT_FAILED, Level::Error);
        static::assertSame(PayPalApiException::class, $record->context[Log::EXCEPTION]['class'] ?? null);
    }

    public function testExecuteWebhookThrowsWebhookException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_WEBHOOK_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('An error occurred during execution of webhook', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $record = $this->assertPayPalLogged(LogCode::WEBHOOK_EVENT_INVALID, Level::Error);
        static::assertSame(WebhookException::class, $record->context[Log::EXCEPTION]['class'] ?? null);
    }

    public function testExecuteWebhookThrowsGeneralException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_GENERAL_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('An error occurred during execution of webhook', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $record = $this->assertPayPalLogged(LogCode::WEBHOOK_EVENT_FAILED, Level::Error);
        static::assertSame(\RuntimeException::class, $record->context[Log::EXCEPTION]['class'] ?? null);
    }

    public function testExecuteWebhookEmptyToken(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::EMPTY_TOKEN, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('Shopware token is invalid', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertPayPalLogged(LogCode::WEBHOOK_TOKEN_INVALID, Level::Warning, [Log::WEBHOOK_EVENT_ID => WebhookDataFixture::get()['id']]);
    }

    public function testExecuteWebhookEmptyTokenSent(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('Shopware token is invalid', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testExecuteWebhookInvalidToken(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'invalid-token']
        );

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('Shopware token is invalid', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testExecuteWebhookInvalidTokenWithId(): void
    {
        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();
        $request->query->set(WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME, 'invalid-token');

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame('Shopware token is invalid', $response->getContent());
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $record = $this->assertPayPalLogged(LogCode::WEBHOOK_TOKEN_INVALID, Level::Warning, [Log::WEBHOOK_EVENT_ID => WebhookDataFixture::get()['id']]);
        static::assertStringNotContainsString('invalid-token', \json_encode($record->context, \JSON_THROW_ON_ERROR));
    }

    public function testExecuteWebhookNoData(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN]
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('No webhook data sent', '/') . '\z/');
        $this->controller->executeWebhook($request, $context);
    }

    public function testExecuteWebhookHandlerNotFound(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_NOT_FOUND_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $ignored = \array_filter(
            $this->getPayPalLogRecords(),
            static fn (LogRecord $record) => $record->message === 'Ignored PayPal webhook event {webhookEventType}: the plugin doesn\'t handle this event type',
        );
        static::assertCount(1, $ignored);
        static::assertSame(Level::Debug, \reset($ignored)->level);
        static::assertSame([], \array_filter($this->getPayPalLogRecords(), static fn (LogRecord $record) => $record->level === Level::Info));
    }

    public function testExecuteWebhookTransactionUnknown(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_TRANSACTION_UNKNOWN_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $record = $this->assertPayPalLogged(LogCode::WEBHOOK_TRANSACTION_UNKNOWN, Level::Warning);
        static::assertSame(WebhookOrderTransactionNotFoundException::class, $record->context[Log::EXCEPTION]['class'] ?? null);
    }

    public function testExecuteWebhookLogsTheRawPayload(): void
    {
        $request = $this->createRequestWithWebhookData();

        $this->controller->executeWebhook($request, Context::createDefaultContext());

        $records = \array_values(\array_filter(
            $this->getPayPalLogRecords(),
            static fn (LogRecord $record) => $record->message === 'Received a PayPal webhook event',
        ));
        static::assertCount(1, $records);
        static::assertSame(Level::Debug, $records[0]->level);
        static::assertSame(WebhookDataFixture::get(), $records[0]->context[Log::PAYLOAD]);
        static::assertArrayNotHasKey(Log::CODE, $records[0]->context);
    }

    /**
     * @return array{WebhookController, WebhookUrlBuilder}
     */
    private function createRegisteringController(string $appUrl, Request $currentRequest = new Request()): array
    {
        $routes = new RouteCollection();
        $routes->add('api.action.paypal.webhook.execute', new Route('/api/_action/paypal/webhook/execute'));
        $router = new Router(new ClosureLoader(), static fn () => $routes, [], new RequestContext(host: 'shop.example', scheme: 'http'));
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $builder = new WebhookUrlBuilder($router, $requestStack, $appUrl);

        $webhookService = $this->createMock(WebhookServiceInterface::class);
        $webhookService->method('registerWebhook')->willReturnCallback(static fn () => $builder->build('t'));

        $controllerRequestStack = new RequestStack();
        $controllerRequestStack->push($currentRequest);

        return [
            new WebhookController($this->createPayPalLogger(), $webhookService, $this->createMock(EntityRepository::class), $builder, $controllerRequestStack),
            $builder,
        ];
    }

    private function createRequestWithWebhookData(): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            WebhookDataFixture::get()
        );
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Test\Webhook\_fixtures\WebhookDataFixture;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public const THROW_PAYPAL_API_EXCEPTION = 'executeWebhookThrowsPayPalApiException';
    public const THROW_WEBHOOK_EXCEPTION = 'executeWebhookThrowsWebhookException';
    public const THROW_GENERAL_EXCEPTION = 'executeWebhookThrowsGeneralException';
    public const THROW_NOT_FOUND_EXCEPTION = 'executeWebhookThrowsNotFoundException';
    public const EMPTY_TOKEN = 'emptyExecuteToken';

    private WebhookController $controller;

    private TestHandler $logger;

    protected function setUp(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], $this->createMock(ContainerInterface::class));
        $systemConfigRepo = $definitionRegistry->getRepository(
            (new SystemConfigDefinition())->getEntityName()
        );

        $this->logger = new TestHandler();

        $this->controller = new WebhookController(
            new Logger('testlogger', [$this->logger]),
            new WebhookServiceMock(SystemConfigServiceMock::createWithoutCredentials()),
            $systemConfigRepo
        );
    }

    public function testStatusWebhook(): void
    {
        $jsonResponse = $this->controller->statusWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        static::assertEqualsCanonicalizing(['result' => WebhookService::STATUS_WEBHOOK_VALID], $result);
    }

    public function testRegisterWebhook(): void
    {
        $jsonResponse = $this->controller->registerWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        static::assertEqualsCanonicalizing(['result' => WebhookService::WEBHOOK_CREATED], $result);
    }

    public function testDeregisterWebhook(): void
    {
        $jsonResponse = $this->controller->deregisterWebhook(TestDefaults::SALES_CHANNEL);
        $content = $jsonResponse->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);

        // no action because no Webhook ID is defined by default
        static::assertEqualsCanonicalizing(['result' => WebhookService::NO_WEBHOOK_ACTION_REQUIRED], $result);
    }

    public function testExecuteWebhook(): void
    {
        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        static::assertTrue(
            $this->logger->hasRecordThatContains('Webhook successfully executed', Level::Info),
            'Expected "Webhook successfully executed" log entry not found',
        );
    }

    public function testExecuteWebhookThrowsPayPalApiException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_PAYPAL_API_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $this->controller->executeWebhook($request, $context);

        static::assertTrue(
            $this->logger->hasRecordThatContains('testPayPalApiExceptionMessage', Level::Error),
            'Expected "testPayPalApiExceptionMessage" log entry not found',
        );
    }

    public function testExecuteWebhookThrowsWebhookException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_WEBHOOK_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $this->controller->executeWebhook($request, $context);

        static::assertTrue(
            $this->logger->hasRecordThatContains('testWebhookExceptionMessage', Level::Error),
            'Expected "testWebhookExceptionMessage" log entry not found',
        );
    }

    public function testExecuteWebhookThrowsGeneralException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_GENERAL_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $this->controller->executeWebhook($request, $context);

        static::assertTrue(
            $this->logger->hasRecordThatContains('testGeneralExceptionMessage', Level::Error),
            'Expected "testGeneralExceptionMessage" log entry not found',
        );
    }

    public function testExecuteWebhookEmptyToken(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::EMPTY_TOKEN, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->controller->executeWebhook($request, $context);
    }

    public function testExecuteWebhookEmptyTokenSent(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->controller->executeWebhook($request, $context);
    }

    public function testExecuteWebhookInvalidToken(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'invalid-token']
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->controller->executeWebhook($request, $context);
    }

    public function testExecuteWebhookNoData(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN]
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('No webhook data sent');
        $this->controller->executeWebhook($request, $context);
    }

    public function testExecuteWebhookHandlerNotFound(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_NOT_FOUND_EXCEPTION, new ArrayStruct());
        $request = $this->createRequestWithWebhookData();

        $response = $this->controller->executeWebhook($request, $context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        static::assertTrue(
            $this->logger->hasRecordThatContains('No webhook handler found', Level::Info),
            'Expected "No webhook handler found" log entry not found',
        );
    }

    private function createRequestWithWebhookData(): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            WebhookDataFixture::get()
        );
    }
}

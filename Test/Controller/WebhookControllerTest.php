<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Test\TestCaseBase\AssertArraySubsetBehaviour;
use SwagPayPal\Controller\WebhookController;
use SwagPayPal\Test\Controller\_fixtures\WebhookDataFixture;
use SwagPayPal\Test\Mock\DIContainerMock;
use SwagPayPal\Test\Mock\LoggerMock;
use SwagPayPal\Test\Mock\Repositories\DefinitionRegistryMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use SwagPayPal\Test\Mock\Webhook\WebhookServiceMock;
use SwagPayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhookControllerTest extends TestCase
{
    use AssertArraySubsetBehaviour;

    public const THROW_WEBHOOK_EXCEPTION = 'executeWebhookThrowsWebhookException';

    public const THROW_GENERAL_EXCEPTION = 'executeWebhookThrowsGeneralException';

    public function testRegisterWebhook(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $jsonResponse = $webhookController->registerWebhook($context);
        $result = json_decode($jsonResponse->getContent(), true);

        $this->silentAssertArraySubset(['result' => WebhookService::WEBHOOK_CREATED], $result);
    }

    public function testExecuteWebhook(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();
        $response = $webhookController->executeWebhook($request, $context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testExecuteWebhookThrowsWebhookException(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_WEBHOOK_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookThrowsGeneralException(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_GENERAL_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookEmptyToken(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $request = new Request();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookInvalidToken(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'invalid-token']
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookNoData(): void
    {
        $webhookController = $this->createWebhookController();

        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => SettingsServiceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN]
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('No webhook data sent');
        $webhookController->executeWebhook($request, $context);
    }

    private function createWebhookController(): WebhookController
    {
        return new WebhookController(
            new LoggerMock(),
            new WebhookServiceMock(),
            new SettingsServiceMock(new DefinitionRegistryMock([], new DIContainerMock()))
        );
    }

    private function createRequestWithWebhookData(): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => SettingsServiceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            WebhookDataFixture::get()
        );
    }
}

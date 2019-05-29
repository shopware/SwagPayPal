<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Test\TestCaseBase\AssertArraySubsetBehaviour;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Test\Controller\_fixtures\WebhookDataFixture;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhookControllerTest extends TestCase
{
    use AssertArraySubsetBehaviour;
    use ServicesTrait;

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
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $webhookController = $this->createWebhookController($settings);

        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();
        $response = $webhookController->executeWebhook($request, $context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testExecuteWebhookThrowsWebhookException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $webhookController = $this->createWebhookController($settings);

        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_WEBHOOK_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookThrowsGeneralException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $webhookController = $this->createWebhookController($settings);

        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_GENERAL_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookEmptyToken(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken('');
        $webhookController = $this->createWebhookController($settings);

        $context = Context::createDefaultContext();
        $request = new Request();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $webhookController->executeWebhook($request, $context);
    }

    public function testExecuteWebhookInvalidToken(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $webhookController = $this->createWebhookController($settings);

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
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $webhookController = $this->createWebhookController($settings);

        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN]
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('No webhook data sent');
        $webhookController->executeWebhook($request, $context);
    }

    private function createWebhookController(?SwagPayPalSettingGeneralStruct $settings = null): WebhookController
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($settings);

        return new WebhookController(
            new LoggerMock(),
            new WebhookServiceMock(),
            $settingsService
        );
    }

    private function createRequestWithWebhookData(): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            WebhookDataFixture::get()
        );
    }
}

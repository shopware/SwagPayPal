<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Test\TestCaseBase\AssertArraySubsetBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookReturnCreatedResourceMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Test\Webhook\_fixtures\WebhookDataFixture;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhookControllerTest extends TestCase
{
    use AssertArraySubsetBehaviour;
    use ServicesTrait;
    use IntegrationTestBehaviour;

    public const THROW_WEBHOOK_EXCEPTION = 'executeWebhookThrowsWebhookException';
    public const THROW_GENERAL_EXCEPTION = 'executeWebhookThrowsGeneralException';
    public const EMPTY_TOKEN = 'emptyExecuteToken';

    public function testRegisterWebhook(): void
    {
        $jsonResponse = $this->createWebhookController()->registerWebhook(Defaults::SALES_CHANNEL);
        static::assertNotFalse($jsonResponse->getContent());

        $result = json_decode($jsonResponse->getContent(), true);

        $this->silentAssertArraySubset(['result' => WebhookService::WEBHOOK_CREATED], $result);
    }

    public function testExecuteWebhook(): void
    {
        $context = Context::createDefaultContext();
        $request = $this->createRequestWithWebhookData();

        $response = $this->createWebhookController()->executeWebhook($request, $context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testExecuteWebhookThrowsWebhookException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_WEBHOOK_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    public function testExecuteWebhookThrowsGeneralException(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_GENERAL_EXCEPTION, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('An error occurred during execution of webhook');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    public function testExecuteWebhookEmptyToken(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(self::EMPTY_TOKEN, new Entity());
        $request = $this->createRequestWithWebhookData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    public function testExecuteWebhookEmptyTokenSent(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    public function testExecuteWebhookInvalidToken(): void
    {
        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'invalid-token']
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Shopware token is invalid');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    public function testExecuteWebhookNoData(): void
    {

        $context = Context::createDefaultContext();
        $request = new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookReturnCreatedResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN]
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('No webhook data sent');
        $this->createWebhookController()->executeWebhook($request, $context);
    }

    private function createWebhookController(): WebhookController
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $systemConfigRepo = $definitionRegistry->getRepository(
            (new SystemConfigDefinition())->getEntityName()
        );

        return new WebhookController(
            new LoggerMock(),
            new WebhookServiceMock(),
            $systemConfigRepo
        );
    }

    private function createRequestWithWebhookData(): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookReturnCreatedResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            WebhookDataFixture::get()
        );
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\V1\PatchCollection;
use Shopware\PayPalSDK\Struct\V1\Webhook;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Test\Mock\PayPalSDK\MockRequestHandler;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Exception\WebhookValidationError;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookResourceTest extends TestCase
{
    use GatewayTestBehaviour;
    use ServicesTrait;

    public const THROW_EXCEPTION_WITH_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithResponse';

    public const THROW_EXCEPTION_INVALID_ID = 'getWebhookUrlShouldThrowExceptionWithInvalidResourceId';

    public const THROW_EXCEPTION_INVALID_URL = 'updateWebhookUrlShouldThrowExceptionWithInvalidWebhookUrl';

    public const TEST_URL = 'testUrl';

    public const TEST_URL_ALREADY_EXISTS = 'alreadyExistingTestUrl';

    public const TEST_URL_INVALID = 'invalidTestUrl';

    public function testCreateWebhook(): void
    {
        $webhookId = $this->createWebHookResource()->createWebhook('url', new Webhook(), TestDefaults::SALES_CHANNEL);

        static::assertSame(MockRequestHandler::TEST_WEBHOOK_ID, $webhookId);
    }

    public function testCreateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new Webhook();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . MockRequestHandler::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->createWebhook(self::TEST_URL, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testCreateWebhookThrowsInvalidWebhookException(): void
    {
        $createWebhooks = new Webhook();
        $createWebhooks->assign(['url' => self::TEST_URL_INVALID]);

        $this->expectException(WebhookValidationError::class);
        $this->expectExceptionMessage(\sprintf('Provided webhook URL "%s" is invalid', self::TEST_URL_INVALID));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_INVALID, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testCreateWebhookThrowsExceptionWebhookAlreadyExists(): void
    {
        $createWebhooks = new Webhook();
        $createWebhooks->assign(['url' => self::TEST_URL_ALREADY_EXISTS]);

        $this->expectException(WebhookAlreadyExistsException::class);
        $this->expectExceptionMessage(\sprintf('WebhookUrl "%s" already exists', self::TEST_URL_ALREADY_EXISTS));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_ALREADY_EXISTS, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testGetWebhookUrl(): void
    {
        $webhookUrl = $this->createWebHookResource()->getWebhookUrl(MockRequestHandler::TEST_WEBHOOK_ID, TestDefaults::SALES_CHANNEL);

        static::assertSame(MockRequestHandler::GET_WEBHOOK_URL, $webhookUrl);
    }

    public function testGetWebhookUrlThrowsExceptionInvalidId(): void
    {
        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(\sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_INVALID_ID, TestDefaults::SALES_CHANNEL);
    }

    public function testGetWebhookUrlThrowsExceptionWithResponse(): void
    {
        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . MockRequestHandler::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_WITH_RESPONSE, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhook(): void
    {
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, '', TestDefaults::SALES_CHANNEL);

        $body = self::getClient()->lastWhere(static fn ($context) => $context->getRequest()->getMethod() === 'PATCH')?->getRequestBody();
        static::assertIsArray($body);
        $patches = PatchCollection::createFromAssociative($body);
        static::assertCount(1, $patches);
        static::assertSame(self::TEST_URL, $patches->getAt(0)?->getValue());
    }

    public function testUpdateWebhookWithInvalidResourceId(): void
    {
        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(\sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->updateWebhook('', self::THROW_EXCEPTION_INVALID_ID, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhookThrowsInvalidUrlException(): void
    {
        $createWebhooks = new Webhook();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . MockRequestHandler::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->updateWebhook('', MockRequestHandler::TEST_WEBHOOK_ID, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new Webhook();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(WebhookValidationError::class);
        $this->expectExceptionMessage(\sprintf('Provided webhook URL "%s" is invalid', self::TEST_URL));
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, self::THROW_EXCEPTION_INVALID_URL, TestDefaults::SALES_CHANNEL);
    }

    private function createWebHookResource(): WebhookResource
    {
        return new WebhookResource(self::webhookGateway(), new ApiContextFactoryMock());
    }
}

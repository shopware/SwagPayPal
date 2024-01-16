<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\CreateWebhooks;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Exception\WebhookValidationError;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookResourceTest extends TestCase
{
    use ServicesTrait;

    public const THROW_EXCEPTION_WITH_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithResponse';

    public const THROW_EXCEPTION_INVALID_ID = 'getWebhookUrlShouldThrowExceptionWithInvalidResourceId';

    public const THROW_EXCEPTION_INVALID_URL = 'updateWebhookUrlShouldThrowExceptionWithInvalidWebhookUrl';

    public const TEST_URL = 'testUrl';

    public const TEST_URL_ALREADY_EXISTS = 'alreadyExistingTestUrl';

    public const TEST_URL_INVALID = 'invalidTestUrl';

    private PayPalClientFactoryMock $clientFactory;

    public function testCreateWebhook(): void
    {
        $webhookId = $this->createWebHookResource()->createWebhook('url', new CreateWebhooks(), TestDefaults::SALES_CHANNEL);

        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $webhookId);
    }

    public function testCreateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . GuzzleClientMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->createWebhook(self::TEST_URL, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testCreateWebhookThrowsInvalidWebhookException(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL_INVALID]);

        $this->expectException(WebhookValidationError::class);
        $this->expectExceptionMessage(\sprintf('Provided webhook URL "%s" is invalid', self::TEST_URL_INVALID));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_INVALID, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testCreateWebhookThrowsExceptionWebhookAlreadyExists(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL_ALREADY_EXISTS]);

        $this->expectException(WebhookAlreadyExistsException::class);
        $this->expectExceptionMessage(\sprintf('WebhookUrl "%s" already exists', self::TEST_URL_ALREADY_EXISTS));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_ALREADY_EXISTS, $createWebhooks, TestDefaults::SALES_CHANNEL);
    }

    public function testGetWebhookUrl(): void
    {
        $webhookUrl = $this->createWebHookResource()->getWebhookUrl(GuzzleClientMock::TEST_WEBHOOK_ID, TestDefaults::SALES_CHANNEL);

        static::assertSame(GuzzleClientMock::GET_WEBHOOK_URL, $webhookUrl);
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
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . GuzzleClientMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_WITH_RESPONSE, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhook(): void
    {
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, '', TestDefaults::SALES_CHANNEL);

        $data = $this->clientFactory->getClient()->getData();
        $patchJsonString = \json_encode($data[0]);
        static::assertNotFalse($patchJsonString);

        $patch = \json_decode($patchJsonString, true);
        static::assertSame($patch['value'], self::TEST_URL);
    }

    public function testUpdateWebhookWithInvalidResourceId(): void
    {
        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(\sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->updateWebhook('', self::THROW_EXCEPTION_INVALID_ID, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhookThrowsInvalidUrlException(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: ' . GuzzleClientMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $this->createWebHookResource()->updateWebhook('', GuzzleClientMock::TEST_WEBHOOK_ID, TestDefaults::SALES_CHANNEL);
    }

    public function testUpdateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(WebhookValidationError::class);
        $this->expectExceptionMessage(\sprintf('Provided webhook URL "%s" is invalid', self::TEST_URL));
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, self::THROW_EXCEPTION_INVALID_URL, TestDefaults::SALES_CHANNEL);
    }

    private function createWebHookResource(): WebhookResource
    {
        $this->clientFactory = new PayPalClientFactoryMock(new NullLogger());

        return new WebhookResource(
            $this->clientFactory
        );
    }
}

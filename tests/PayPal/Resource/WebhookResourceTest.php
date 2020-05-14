<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientMock;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookResourceTest extends TestCase
{
    use ServicesTrait;

    public const THROW_EXCEPTION_WITHOUT_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithoutResponse';

    public const THROW_EXCEPTION_WITH_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithResponse';

    public const THROW_EXCEPTION_INVALID_ID = 'getWebhookUrlShouldThrowExceptionWithInvalidResourceId';

    public const WEBHOOK_ID = 'testWebhookId';

    public const TEST_URL = 'testUrl';

    public const TEST_URL_ALREADY_EXISTS = 'alreadyExistingTestUrl';

    /**
     * @var PayPalClientFactoryMock
     */
    private $clientFactory;

    public function testCreateWebhook(): void
    {
        $webhookId = $this->createWebHookResource()->createWebhook('url', new CreateWebhooks(), Defaults::SALES_CHANNEL);

        static::assertSame(PayPalClientMock::TEST_WEBHOOK_ID, $webhookId);
    }

    public function testCreateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->createWebhook(self::TEST_URL, $createWebhooks, Defaults::SALES_CHANNEL);
    }

    public function testCreateWebhookThrowsExceptionWebhookAlreadyExists(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL_ALREADY_EXISTS]);

        $this->expectException(WebhookAlreadyExistsException::class);
        $this->expectExceptionMessage(\sprintf('WebhookUrl "%s" already exists', self::TEST_URL_ALREADY_EXISTS));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_ALREADY_EXISTS, $createWebhooks, Defaults::SALES_CHANNEL);
    }

    public function testGetWebhookUrl(): void
    {
        $webhookUrl = $this->createWebHookResource()->getWebhookUrl(self::WEBHOOK_ID, Defaults::SALES_CHANNEL);

        static::assertSame(PayPalClientMock::GET_WEBHOOK_URL, $webhookUrl);
    }

    public function testGetWebhookUrlThrowsExceptionInvalidId(): void
    {
        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(\sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_INVALID_ID, Defaults::SALES_CHANNEL);
    }

    public function testGetWebhookUrlThrowsExceptionWithResponse(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_WITH_RESPONSE, Defaults::SALES_CHANNEL);
    }

    public function testUpdateWebhook(): void
    {
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, '', Defaults::SALES_CHANNEL);

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
        $this->createWebHookResource()->updateWebhook('', self::THROW_EXCEPTION_INVALID_ID, Defaults::SALES_CHANNEL);
    }

    public function testUpdateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->updateWebhook('', self::WEBHOOK_ID, Defaults::SALES_CHANNEL);
    }

    private function createWebHookResource(): WebhookResource
    {
        $this->clientFactory = $this->createPayPalClientFactory();

        return new WebhookResource(
            $this->clientFactory
        );
    }
}

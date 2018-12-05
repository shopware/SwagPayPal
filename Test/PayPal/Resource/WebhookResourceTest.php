<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\CreateWebhooks;
use SwagPayPal\PayPal\Resource\WebhookResource;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookResourceTest extends TestCase
{
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
        $context = Context::createDefaultContext();
        $webhookId = $this->createWebHookResource()->createWebhook('url', new CreateWebhooks(), $context);

        self::assertSame(PayPalClientMock::TEST_WEBHOOK_ID, $webhookId);
    }

    public function testCreateWebhookThrowsExceptionWithResponse(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);
        $context = Context::createDefaultContext();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->createWebhook(self::TEST_URL, $createWebhooks, $context);
    }

    public function testCreateWebhookThrowsExceptionWebhookAlreadyExists(): void
    {
        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL_ALREADY_EXISTS]);
        $context = Context::createDefaultContext();

        $this->expectException(WebhookAlreadyExistsException::class);
        $this->expectExceptionMessage(sprintf('WebhookUrl "%s" already exists', self::TEST_URL_ALREADY_EXISTS));
        $this->createWebHookResource()->createWebhook(self::TEST_URL_ALREADY_EXISTS, $createWebhooks, $context);
    }

    public function testGetWebhookUrl(): void
    {
        $context = Context::createDefaultContext();
        $webhookUrl = $this->createWebHookResource()->getWebhookUrl(self::WEBHOOK_ID, $context);

        self::assertSame(PayPalClientMock::GET_WEBHOOK_URL, $webhookUrl);
    }

    public function testGetWebhookUrlThrowsExceptionWithoutResponse(): void
    {
        self::markTestSkipped('Currently skipped, because Guzzle throws a deprecation message, which causes a failed build on bamboo');
        $context = Context::createDefaultContext();
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITHOUT_RESPONSE);
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_WITHOUT_RESPONSE, $context);
    }

    public function testGetWebhookUrlThrowsExceptionInvalidId(): void
    {
        $context = Context::createDefaultContext();

        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_INVALID_ID, $context);
    }

    public function testGetWebhookUrlThrowsExceptionWithResponse(): void
    {
        $context = Context::createDefaultContext();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->getWebhookUrl(self::THROW_EXCEPTION_WITH_RESPONSE, $context);
    }

    public function testUpdateWebhook(): void
    {
        $context = Context::createDefaultContext();
        $this->createWebHookResource()->updateWebhook(self::TEST_URL, '', $context);

        $data = $this->clientFactory->getClient()->getData();
        $patch = json_decode(json_encode($data[0]), true);
        self::assertSame($patch['value'], self::TEST_URL);
    }

    public function testUpdateWebhookWithInvalidResourceId(): void
    {
        $context = Context::createDefaultContext();

        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $this->createWebHookResource()->updateWebhook('', self::THROW_EXCEPTION_INVALID_ID, $context);
    }

    public function testUpdateWebhookThrowsExceptionWithResponse(): void
    {
        $context = Context::createDefaultContext();

        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign(['url' => self::TEST_URL]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $this->createWebHookResource()->updateWebhook('', self::WEBHOOK_ID, $context);
    }

    private function createWebHookResource(): WebhookResource
    {
        $this->clientFactory = new PayPalClientFactoryMock(
            new TokenResourceMock(
                new CacheMock(),
                new TokenClientFactoryMock()
            ),
            new SettingsProviderMock()
        );

        return new WebhookResource(
            $this->clientFactory
        );
    }
}

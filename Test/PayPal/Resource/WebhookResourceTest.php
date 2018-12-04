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
use SwagPayPal\PayPal\Resource\WebhookResource;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookResourceTest extends TestCase
{
    public const THROW_EXCEPTION_WITHOUT_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithoutResponse';

    public const THROW_EXCEPTION_WITH_RESPONSE = 'getWebhookUrlShouldThrowExceptionWithResponse';

    public const THROW_EXCEPTION_INVALID_ID = 'getWebhookUrlShouldThrowExceptionWithInvalidResourceId';

    private const WEBHOOK_ID = 'testWebhookId';

    public function testGetWebhookUrl(): void
    {
        $webhookResource = $this->createWebHookResource();

        $context = Context::createDefaultContext();
        $webhookUrl = $webhookResource->getWebhookUrl(self::WEBHOOK_ID, $context);

        self::assertSame(PayPalClientMock::GET_WEBHOOK_URL, $webhookUrl);
    }

    public function testGetWebhookUrlThrowsExceptionWithoutResponse(): void
    {
        self::markTestSkipped('Currently skipped, because Guzzle throws a deprecation message, which causes a failed build on bamboo');
        $webhookResource = $this->createWebHookResource();

        $context = Context::createDefaultContext();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITHOUT_RESPONSE);
        $webhookResource->getWebhookUrl(self::THROW_EXCEPTION_WITHOUT_RESPONSE, $context);
    }

    public function testGetWebhookUrlThrowsExceptionInvalidId(): void
    {
        $webhookResource = $this->createWebHookResource();

        $context = Context::createDefaultContext();

        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $webhookResource->getWebhookUrl(self::THROW_EXCEPTION_INVALID_ID, $context);
    }

    public function testGetWebhookUrlThrowsExceptionWithResponse(): void
    {
        $webhookResource = $this->createWebHookResource();

        $context = Context::createDefaultContext();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(PayPalClientMock::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE);
        $webhookResource->getWebhookUrl(self::THROW_EXCEPTION_WITH_RESPONSE, $context);
    }

    public function testUpdateWebhook(): void
    {
        $webhookResource = $this->createWebHookResource();

        $context = Context::createDefaultContext();

        $this->expectException(WebhookIdInvalidException::class);
        $this->expectExceptionMessage(sprintf('Webhook with ID "%s" is invalid', self::THROW_EXCEPTION_INVALID_ID));
        $webhookResource->updateWebhook('', self::THROW_EXCEPTION_INVALID_ID, $context);
    }

    private function createWebHookResource(): WebhookResource
    {
        return new WebhookResource(
            new PayPalClientFactoryMock(
                new TokenResourceMock(
                    new CacheMock(),
                    new TokenClientFactoryMock()
                ),
                new SettingsProviderMock()
            )
        );
    }
}

<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\DummyCollection;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\Exception\WebhookException;
use SwagPayPal\Webhook\WebhookRegistry;

class WebhookRegistryTest extends TestCase
{
    use ServicesTrait;

    public function testGetWebhookHandler(): void
    {
        $webhook = $this->createWebhookRegistry()->getWebhookHandler(DummyWebhook::EVENT_TYPE);

        self::assertInstanceOf(DummyWebhook::class, $webhook);
    }

    public function testGetUnknownWebhookHandler(): void
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('The specified event-type does not exist.');
        $this->createWebhookRegistry()->getWebhookHandler('Foo');
    }

    public function testRegisterAlreadyRegisteredWebhook(): void
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('The specified event is already registered.');
        new WebhookRegistry(new DummyCollection([$this->createDummyWebhook(), $this->createDummyWebhook()]));
    }
}

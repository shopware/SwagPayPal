<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookRegistry;

class WebhookRegistryTest extends TestCase
{
    use ServicesTrait;

    public function testGetWebhookHandler(): void
    {
        $webhook = $this->createWebhookRegistry()->getWebhookHandler(DummyWebhook::EVENT_TYPE);

        static::assertInstanceOf(DummyWebhook::class, $webhook);
    }

    public function testGetUnknownWebhookHandler(): void
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('No webhook handler found for event "Foo". Shopware does not need to handle this event.');
        $this->createWebhookRegistry()->getWebhookHandler('Foo');
    }

    public function testRegisterAlreadyRegisteredWebhook(): void
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('The specified event is already registered.');
        new WebhookRegistry(new DummyCollection([$this->createDummyWebhook(), $this->createDummyWebhook()]));
    }
}

<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookRegistry;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookRegistryTest extends TestCase
{
    public function testGetWebhookHandler(): void
    {
        $registry = new WebhookRegistry([new DummyWebhook(new OrderTransactionRepoMock())]);
        $webhook = $registry->getWebhookHandler(DummyWebhook::EVENT_TYPE);

        static::assertInstanceOf(DummyWebhook::class, $webhook);
    }

    public function testGetUnknownWebhookHandler(): void
    {
        $registry = new WebhookRegistry([new DummyWebhook(new OrderTransactionRepoMock())]);

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('No webhook handler found for event "Foo". Shopware does not need to handle this event.');
        $registry->getWebhookHandler('Foo');
    }

    public function testRegisterAlreadyRegisteredWebhook(): void
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('The specified event is already registered.');
        new WebhookRegistry([new DummyWebhook(new OrderTransactionRepoMock()), new DummyWebhook(new OrderTransactionRepoMock())]);
    }
}

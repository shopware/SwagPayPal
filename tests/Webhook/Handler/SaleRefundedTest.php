<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Sale;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Handler\SaleRefunded;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class SaleRefundedTest extends AbstractWebhookHandlerTestCase
{
    private Sale $sale;

    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_SALE_REFUNDED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV1();
        $this->assertInvoke(OrderTransactionStates::STATE_REFUNDED, $webhook, OrderTransactionStates::STATE_PAID);
    }

    public function testInvokeWithPartialRefund(): void
    {
        $webhook = $this->createWebhookV1();
        $this->sale->setState(PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED);
        $this->assertInvoke(OrderTransactionStates::STATE_PARTIALLY_REFUNDED, $webhook, OrderTransactionStates::STATE_PAID);
    }

    public function testInvokeWithoutParentPayment(): void
    {
        $this->assertInvokeWithoutParentPayment(WebhookEventTypes::PAYMENT_SALE_REFUNDED);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $webhook = $this->createWebhookV1(OrderTransactionRepoMock::WEBHOOK_WITHOUT_TRANSACTION);
        $reason = \sprintf('with the PayPal ID "%s"', OrderTransactionRepoMock::WEBHOOK_WITHOUT_TRANSACTION);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_SALE_REFUNDED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV1();
        $this->assertInvoke(OrderTransactionStates::STATE_REFUNDED, $webhook, OrderTransactionStates::STATE_REFUNDED);
    }

    protected function createWebhookHandler(): SaleRefunded
    {
        $saleResource = $this->createMock(SaleResource::class);
        $this->sale = (new Sale())->assign(GetResourceSaleResponseFixture::get());
        $this->sale->setState(PaymentStatusV1::PAYMENT_REFUNDED);
        $saleResource->method('get')->willReturn($this->sale);

        return new SaleRefunded(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $saleResource,
        );
    }
}

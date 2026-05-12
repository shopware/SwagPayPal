<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order;

#[Package('checkout')]
class APMHandler extends AbstractPaymentMethodHandler
{
    protected function isVaultable(): bool
    {
        return false;
    }

    protected function requirePreparedOrder(): bool
    {
        return false;
    }

    protected function executeOrder(PaymentTransactionStruct $transaction, Order $paypalOrder, OrderEntity $order, OrderTransactionEntity $orderTransaction, Context $context, bool $isUserPresent = true): Order
    {
        // Orders of APMs are created with `ORDER_COMPLETE_ON_PAYMENT_APPROVAL`
        // Therefore it will automatically capture the payment
        return $paypalOrder;
    }
}

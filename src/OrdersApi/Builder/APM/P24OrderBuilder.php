<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\P24;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class P24OrderBuilder extends AbstractAPMOrderBuilder
{
    protected function buildPaymentSource(
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
        PaymentSource $paymentSource,
    ): void {
        $sourceElement = new P24();
        $this->fillPaymentSource($paymentTransaction, $order, $context, $sourceElement);

        $customer = $order->getOrderCustomer();
        if ($customer === null) {
            throw OrderException::orderNotFound($order->getId());
        }
        $sourceElement->setEmail($customer->getEmail());

        $paymentSource->setP24($sourceElement);
    }
}

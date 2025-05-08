<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\AbstractAPMPaymentSource;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Exception\OrderBuildException;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractAPMOrderBuilder extends AbstractOrderBuilder
{
    public function getOrder(
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
    ): Order {
        $order = parent::getOrder($paymentTransaction, $orderTransaction, $order, $context, $request);
        $order->setIntent(ConstantsV2::INTENT_CAPTURE);
        $order->setProcessingInstruction(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL);

        return $order;
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        throw OrderBuildException::cartNotSupported(static::class);
    }

    protected function fillPaymentSource(
        PaymentTransactionStruct $paymentTransaction,
        OrderEntity $order,
        Context $context,
        AbstractAPMPaymentSource $paymentSource,
    ): void {
        $address = $order->getBillingAddress();
        if ($address === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        $paymentSource->setName(\sprintf('%s %s', $address->getFirstName(), $address->getLastName()));

        $country = $address->getCountry();
        if ($country === null || ($iso = $country->getIso()) === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        $paymentSource->setCountryCode($iso);

        $salesChannel = $order->getSalesChannel();
        \assert($salesChannel !== null);
        $paymentSource->setExperienceContext($this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction));
    }
}

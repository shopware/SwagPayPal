<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Exception\OrderBuildException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractAPMPaymentSource;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

#[Package('checkout')]
abstract class AbstractAPMOrderBuilder extends AbstractOrderBuilder
{
    public function getOrder(SyncPaymentTransactionStruct $paymentTransaction, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag): Order
    {
        $order = parent::getOrder($paymentTransaction, $salesChannelContext, $requestDataBag);
        $order->setIntent(PaymentIntentV2::CAPTURE);
        $order->setProcessingInstruction(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL);

        return $order;
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        throw OrderBuildException::cartNotSupported(static::class);
    }

    protected function fillPaymentSource(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        AbstractAPMPaymentSource $paymentSource,
    ): void {
        $address = $paymentTransaction->getOrder()->getBillingAddress();
        if ($address === null) {
            throw new AddressNotFoundException($paymentTransaction->getOrder()->getBillingAddressId());
        }

        $paymentSource->setName(\sprintf('%s %s', $address->getFirstName(), $address->getLastName()));

        $country = $address->getCountry();
        if ($country === null || ($iso = $country->getIso()) === null) {
            throw new AddressNotFoundException($paymentTransaction->getOrder()->getBillingAddressId());
        }

        $paymentSource->setCountryCode($iso);

        $paymentSource->setExperienceContext($this->createExperienceContext($salesChannelContext, $paymentTransaction));
    }
}

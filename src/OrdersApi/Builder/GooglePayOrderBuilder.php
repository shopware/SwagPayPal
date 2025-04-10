<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Verification;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\GooglePay;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class GooglePayOrderBuilder extends AbstractOrderBuilder
{
    protected function buildPaymentSource(
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
        PaymentSource $paymentSource,
    ): void {
        $salesChannel = $order->getSalesChannel();
        \assert($salesChannel !== null);
        $googlePay = new GooglePay();
        $googlePay->setExperienceContext($this->createExperienceContext($order, $salesChannel, $context, $paymentTransaction));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $googlePay->setAttributes($attributes);

        $paymentSource->setGooglePay($googlePay);
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $googlePay = new GooglePay();
        $googlePay->setExperienceContext($this->createExperienceContext($cart, $salesChannelContext->getSalesChannel(), $salesChannelContext->getContext()));

        $attributes = new Attributes();
        $verification = new Verification();
        $verification->setMethod(Verification::METHOD_SCA_WHEN_REQUIRED);
        $attributes->setVerification($verification);
        $googlePay->setAttributes($attributes);

        $paymentSource->setGooglePay($googlePay);
    }
}

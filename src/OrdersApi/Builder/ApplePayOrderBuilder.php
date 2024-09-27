<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\ApplePay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Verification;

#[Package('checkout')]
class ApplePayOrderBuilder extends AbstractOrderBuilder
{
    protected function buildPaymentSource(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void {
        $applePay = new ApplePay();
        $applePay->setExperienceContext($this->createExperienceContext($salesChannelContext, $paymentTransaction));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $applePay->setAttributes($attributes);

        $paymentSource->setApplePay($applePay);
    }

    protected function buildPaymentSourceFromCart(Cart $cart, SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag, PaymentSource $paymentSource): void
    {
        $applePay = new ApplePay();
        $applePay->setExperienceContext($this->createExperienceContext($salesChannelContext));

        $attributes = new Attributes();
        $attributes->setVerification(new Verification());
        $applePay->setAttributes($attributes);

        $paymentSource->setApplePay($applePay);
    }
}

<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\Api\Payment;

interface CartPaymentBuilderInterface
{
    /**
     * Returns all necessary data to create a payment via the PayPal API. Uses data given by a Shopware cart
     */
    public function getPayment(Cart $cart, SalesChannelContext $salesChannelContext, string $finishUrl, bool $isExpressCheckoutProcess): Payment;
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\ApiV1\Api\Payment;

interface CartPaymentBuilderInterface
{
    /**
     * Returns all necessary data to create a payment via the PayPal API. Uses data given by a Shopware cart
     */
    public function getPayment(Cart $cart, SalesChannelContext $salesChannelContext, string $finishUrl, bool $isExpressCheckoutProcess): Payment;
}

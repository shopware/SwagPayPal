<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V1\Api\Payment;

#[Package('checkout')]
interface OrderPaymentBuilderInterface
{
    /**
     * Returns all necessary data to create a payment via the PayPal API. Uses data given by a Shopware order
     */
    public function getPayment(AsyncPaymentTransactionStruct $paymentTransaction, SalesChannelContext $salesChannelContext): Payment;
}

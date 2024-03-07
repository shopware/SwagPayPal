<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\RestApi\V2\Api\Order;

#[Package('checkout')]
class ApplePayValidator extends AbstractCardValidator
{
    public function validate(Order $order, SyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): bool
    {
        $card = $order->getPaymentSource()?->getApplePay()?->getCard();

        if ($card === null) {
            throw new MissingPayloadException($order->getId(), 'payment_source.apple_pay.card');
        }

        return true;
    }
}

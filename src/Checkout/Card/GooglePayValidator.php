<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;

#[Package('checkout')]
class GooglePayValidator extends AbstractCardValidator implements CardValidatorInterface
{
    /**
     * This implements the recommended actions from PayPal. Feel free to customize.
     *
     * @see https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
     */
    public function validate(Order $order, OrderTransactionEntity $transaction, Context $context): bool
    {
        $card = $order->getPaymentSource()?->getGooglePay()?->getCard();

        if ($card === null) {
            throw new MissingPayloadException($order->getId(), 'payment_source.google_pay.card');
        }

        if (!$card->getAuthenticationResult()) {
            return true;
        }

        return $this->validateAuthenticationResult($card->getAuthenticationResult(), $transaction->getOrder()?->getSalesChannelId());
    }
}

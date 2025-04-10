<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PayPalPaymentHandler extends AbstractPaymentMethodHandler
{
    public const PAYPAL_EXPRESS_CHECKOUT_ID = 'isPayPalExpressCheckout';

    protected function resolvePartnerAttributionId(Request $request): string
    {
        if ($request->request->getBoolean(self::PAYPAL_EXPRESS_CHECKOUT_ID)) {
            return PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT;
        }

        if ($request->request->getAlnum(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)) {
            return PartnerAttributionId::SMART_PAYMENT_BUTTONS;
        }

        return PartnerAttributionId::PAYPAL_PPCP;
    }

    protected function isVaultable(): bool
    {
        return true;
    }

    protected function requirePreparedOrder(): bool
    {
        return false;
    }

    protected function resolveRedirect(?Order $order): ?string
    {
        return parent::resolveRedirect($order) ?? $order?->getLinks()->getRelation(Link::RELATION_APPROVE)?->getHref();
    }
}

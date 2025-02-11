<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Availability;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\Product\State;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AvailabilityContextBuilder
{
    public static function buildAvailabilityContext(Cart $cart, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        $context = new AvailabilityContext();

        if (($customer = $salesChannelContext->getCustomer())
            && ($address = $customer->getActiveBillingAddress())
            && ($country = $address->getCountry())
            && ($isoCode = $country->getIso())) {
            $billingCountryCode = $isoCode;
        } else {
            $billingCountryCode = $salesChannelContext->getShippingLocation()->getCountry()->getIso();
        }

        $context->assign([
            'billingCountryCode' => $billingCountryCode,
            'currencyCode' => $salesChannelContext->getCurrency()->getIsoCode(),
            'totalAmount' => $cart->getPrice()->getTotalPrice(),
            'subscription' => $salesChannelContext->hasExtension('subscription'),
            'salesChannelId' => $salesChannelContext->getSalesChannelId(),
            'hasDigitalProducts' => $cart->getLineItems()->hasLineItemWithState(State::IS_DOWNLOAD),
        ]);

        return $context;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Availability;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
class AvailabilityContextBuilder
{
    public static function buildFromCart(Cart $cart, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        return self::buildContext(
            $salesChannelContext,
            $cart->getPrice()->getTotalPrice(),
            $cart->getLineItems()->hasLineItemWithState(State::IS_DOWNLOAD)
        );
    }

    public static function buildFromProduct(SalesChannelProductEntity $product, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        return self::buildContext(
            $salesChannelContext,
            $product->getCalculatedPrice()->getTotalPrice(),
            self::isDownloadable($product, State::IS_DOWNLOAD)
        );
    }

    private static function buildContext(SalesChannelContext $salesChannelContext, float $price, bool $downloadable): AvailabilityContext
    {
        $context = new AvailabilityContext();

        $context->assign([
            'billingCountryCode' => self::getBillingCountryCode($salesChannelContext),
            'currencyCode' => $salesChannelContext->getCurrency()->getIsoCode(),
            'totalAmount' => $price,
            'subscription' => $salesChannelContext->hasExtension('subscription'),
            'salesChannelId' => $salesChannelContext->getSalesChannelId(),
            'hasDigitalProducts' => $downloadable,
        ]);

        return $context;
    }

    private static function getBillingCountryCode(SalesChannelContext $salesChannelContext): string
    {
        return $salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getCountry()?->getIso()
            ?? $salesChannelContext->getShippingLocation()->getCountry()->getIso() ?? '';
    }

    private static function isDownloadable(SalesChannelProductEntity $product, string $state): bool
    {
        return \in_array($state, $product->getStates(), true);
    }
}

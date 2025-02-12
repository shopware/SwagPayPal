<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Availability;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
final class AvailabilityContextBuilder
{
    private function __construct()
    {
    }

    public static function buildFromCart(Cart $cart, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        return self::buildContext(
            $salesChannelContext,
            $cart->getPrice()->getTotalPrice(),
            $salesChannelContext->hasExtension('subscription'),
            $cart->getLineItems()->hasLineItemWithState(State::IS_DOWNLOAD)
        );
    }

    public static function buildFromProduct(SalesChannelProductEntity $product, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        return self::buildContext(
            $salesChannelContext,
            $product->getCalculatedPrice()->getTotalPrice(),
            $salesChannelContext->hasExtension('subscription'),
            \in_array(State::IS_DOWNLOAD, $product->getStates(), true)
        );
    }

    public static function buildFromOrder(OrderEntity $order, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        return self::buildContext(
            $salesChannelContext,
            $order->getPrice()->getTotalPrice(),
            $order->getExtensionOfType('foreignKeys', ArrayStruct::class)?->get('subscriptionId') !== null,
            (bool) $order->getLineItems()?->hasLineItemWithState(State::IS_DOWNLOAD)
        );
    }

    private static function buildContext(
        SalesChannelContext $salesChannelContext,
        float $price,
        bool $subscription,
        bool $downloadable,
    ): AvailabilityContext {
        $context = new AvailabilityContext();

        $context->assign([
            'billingCountryCode' => self::getBillingCountryCode($salesChannelContext),
            'currencyCode' => $salesChannelContext->getCurrency()->getIsoCode(),
            'totalAmount' => $price,
            'subscription' => $subscription,
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
}

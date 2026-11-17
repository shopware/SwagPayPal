<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractShippingCostRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\ShippingCostRouteResponse;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\Common\Money;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\ShippingOption;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\ShippingOptionCollection;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class ShippingOptionsProvider
{
    /**
     * The shipping cost route deletes the persisted cart before shopware/shopware#19419
     *
     * @deprecated tag:v11.0.0 - Will be removed with 6.8.0.0, which always contains the fix
     */
    private const MIN_SHIPPING_COST_ROUTE_VERSION = '6.7.14.0';

    /**
     * @internal
     */
    public function __construct(
        private readonly PriceFormatter $priceFormatter,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        /**
         * @deprecated tag:v11.0.0 - Will be non-optional with 6.8.0.0
         */
        private readonly ?AbstractShippingCostRoute $shippingCostRoute,
        /**
         * @deprecated tag:v11.0.0 - Will be removed with 6.8.0.0
         */
        private readonly string $shopwareVersion,
    ) {
    }

    public function getShippingOptions(Cart $cart, SalesChannelContext $salesChannelContext): ShippingOptionCollection
    {
        $shippingCosts = $this->loadShippingCosts($cart, $salesChannelContext);

        $shippingMethodOptions = $this->shippingMethodRoute
            ->load(new Request(query: ['onlyAvailable' => '1']), $salesChannelContext, new Criteria())
            ->getShippingMethods()
            ->map(fn ($shippingMethod) => $this->createShippingOption($shippingMethod, $cart, $salesChannelContext, $shippingCosts));

        return new ShippingOptionCollection($shippingMethodOptions);
    }

    private function loadShippingCosts(Cart $cart, SalesChannelContext $salesChannelContext): ?ShippingCostRouteResponse
    {
        if ($this->shippingCostRoute === null || \version_compare($this->shopwareVersion, self::MIN_SHIPPING_COST_ROUTE_VERSION, '<')) {
            return null;
        }

        return $this->shippingCostRoute->shippingCostsCart($cart, $salesChannelContext);
    }

    private function createShippingOption(ShippingMethodEntity $shippingMethod, Cart $cart, SalesChannelContext $salesChannelContext, ?ShippingCostRouteResponse $shippingCosts): ShippingOption
    {
        $option = new ShippingOption();
        $option->setId($shippingMethod->getId());
        $option->setLabel((string) $shippingMethod->getTranslation('name'));

        if ($salesChannelContext->getShippingMethod()->getId() === $shippingMethod->getId()) {
            $option->setAmount($this->createAmount($cart->getShippingCosts(), $cart, $salesChannelContext));
            $option->setSelected(true);
        } elseif ($shippingCost = $shippingCosts?->getShippingCost($shippingMethod->getId())) {
            $option->setAmount($this->createAmount($shippingCost, $cart, $salesChannelContext));
        }

        return $option;
    }

    private function createAmount(CalculatedPrice $shippingCosts, Cart $cart, SalesChannelContext $salesChannelContext): Money
    {
        $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();
        $taxes = $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS ? $shippingCosts->getCalculatedTaxes()->getAmount() : 0.0;

        $amount = new Money();
        $amount->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice() + $taxes, $currencyCode));
        $amount->setCurrencyCode($currencyCode);

        return $amount;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Cart;
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
     * @internal
     */
    public function __construct(
        private readonly PriceFormatter $priceFormatter,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute
    ) {
    }

    public function getShippingOptions(Cart $cart, SalesChannelContext $salesChannelContext): ShippingOptionCollection
    {
        $shippingMethodOptions = $this->shippingMethodRoute
            ->load(new Request(query: ['onlyAvailable' => '1']), $salesChannelContext, new Criteria())
            ->getShippingMethods()
            ->map(fn ($shippingMethod) => $this->createShippingOption($shippingMethod, $cart, $salesChannelContext));

        return new ShippingOptionCollection($shippingMethodOptions);
    }

    private function createShippingOption(ShippingMethodEntity $shippingMethod, Cart $cart, SalesChannelContext $salesChannelContext): ShippingOption
    {
        $option = new ShippingOption();
        $option->setId($shippingMethod->getId());
        $option->setLabel((string) $shippingMethod->getTranslation('name'));

        if ($salesChannelContext->getShippingMethod()->getId() === $shippingMethod->getId()) {
            $option->setSelected(true);

            if ($shippingCosts = $cart->getDeliveries()->first()?->getShippingCosts()) {
                $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();

                $amount = new Money();
                $amount->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice(), $currencyCode));
                $amount->setCurrencyCode($currencyCode);

                $option->setAmount($amount);
            }
        }

        return $option;
    }
}

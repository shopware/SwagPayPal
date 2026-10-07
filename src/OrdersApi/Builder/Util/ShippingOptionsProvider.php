<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOption;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOptionCollection;
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
            $shippingCosts = $cart->getShippingCosts();
            $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();
            $taxes = $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS ? $shippingCosts->getCalculatedTaxes()->getAmount() : 0.0;
            $value = $this->priceFormatter->formatPrice($shippingCosts->getTotalPrice() + $taxes, $currencyCode);

            $amount = new Money();
            $amount->setValue($value);
            $amount->setCurrencyCode($currencyCode);

            $option->setAmount($amount);
            $option->setSelected(true);
        }

        return $option;
    }
}

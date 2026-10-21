<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
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
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly CartService $cartService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getShippingOptions(Cart $cart, SalesChannelContext $salesChannelContext): ShippingOptionCollection
    {
        $shippingMethodOptions = $this->shippingMethodRoute
            ->load(new Request(query: ['onlyAvailable' => '1']), $salesChannelContext, new Criteria())
            ->getShippingMethods()
            ->map(fn ($shippingMethod) => $this->createShippingOption($shippingMethod, $cart, $salesChannelContext));

        // The cart service caches the last calculated cart per token. The recalculations for the
        // unselected shipping methods must not replace the cart of the selected one.
        $this->cartService->setCart($cart);

        return new ShippingOptionCollection($shippingMethodOptions);
    }

    private function createShippingOption(ShippingMethodEntity $shippingMethod, Cart $cart, SalesChannelContext $salesChannelContext): ShippingOption
    {
        $option = new ShippingOption();
        $option->setId($shippingMethod->getId());
        $option->setLabel((string) $shippingMethod->getTranslation('name'));

        if ($salesChannelContext->getShippingMethod()->getId() === $shippingMethod->getId()) {
            $option->setAmount($this->createAmount($cart, $salesChannelContext));
            $option->setSelected(true);

            return $option;
        }

        // PayPal displays shipping options without an amount as 0.00, so the costs
        // of every other shipping method are calculated with a cart of their own.
        $methodCart = $this->calculateCartForShippingMethod($shippingMethod, $salesChannelContext);
        if ($methodCart !== null) {
            $option->setAmount($this->createAmount($methodCart, $salesChannelContext));
        }

        return $option;
    }

    private function calculateCartForShippingMethod(ShippingMethodEntity $shippingMethod, SalesChannelContext $salesChannelContext): ?Cart
    {
        $methodContext = clone $salesChannelContext;
        $methodContext->assign(['shippingMethod' => $shippingMethod]);

        try {
            return $this->cartService->getCart($salesChannelContext->getToken(), $methodContext, false, true);
        } catch (\Throwable $exception) {
            // Keep the option without an amount instead of failing the whole shipping callback
            $this->logger->warning('Shipping callback: could not calculate the shipping costs of a shipping method', [
                'shippingMethodId' => $shippingMethod->getId(),
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function createAmount(Cart $cart, SalesChannelContext $salesChannelContext): Money
    {
        $shippingCosts = $cart->getShippingCosts();
        $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();
        $taxes = $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS ? $shippingCosts->getCalculatedTaxes()->getAmount() : 0.0;

        $amount = new Money();
        $amount->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice() + $taxes, $currencyCode));
        $amount->setCurrencyCode($currencyCode);

        return $amount;
    }
}

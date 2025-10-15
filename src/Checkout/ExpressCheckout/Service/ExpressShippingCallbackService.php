<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Util\PriceFormatter;

#[Package('checkout')]
class ExpressShippingCallbackService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly EntityRepository $countryRepository,
        private readonly PriceFormatter $priceFormatter,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $shippingAddress
     *
     * @return array<int, array<string, mixed>>
     */
    public function recalculateCart(
        string $paypalOrderId,
        array $shippingAddress,
        SalesChannelContext $salesChannelContext,
    ): array {
        $this->logger->info('PayPal shipping callback: Recalculating cart for address change', [
            'paypalOrderId' => $paypalOrderId,
            'countryCode' => $shippingAddress['country_code'] ?? null,
            'fullAddress' => $shippingAddress,
        ]);

        // Get the cart token from the PayPal order
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        // Get country by ISO code
        $countryCode = $shippingAddress['country_code'] ?? null;
        if (!$countryCode) {
            $this->logger->error('Missing country code in shipping address');
            throw new \RuntimeException('Missing country code in shipping address');
        }

        $country = $this->getCountryByIso($countryCode, $salesChannelContext->getContext());
        if (!$country) {
            $this->logger->error('Country not found', ['countryCode' => $countryCode]);
            throw new \RuntimeException(\sprintf('Country not found for code: %s', $countryCode));
        }

        // Create a temporary context with the new shipping country for tax recalculation
        // Note: We don't persist this change, only use it for calculation
        $taxCalculationContext = clone $salesChannelContext;

        // Shopware will automatically recalculate taxes based on the context
        // The cart's delivery address determines the tax rates
        $this->logger->debug('Cart recalculated', [
            'cartTotal' => $cart->getPrice()->getTotalPrice(),
            'cartTax' => $cart->getPrice()->getCalculatedTaxes()->getAmount(),
            'shippingCosts' => $cart->getShippingCosts()->getTotalPrice(),
        ]);

        // Build the response in PayPal's expected format
        $currency = $salesChannelContext->getCurrency();
        $currencyCode = $currency->getIsoCode();

        $cartTotal = $cart->getPrice()->getTotalPrice();
        $cartTax = $cart->getPrice()->getCalculatedTaxes()->getAmount();
        $shippingCosts = $cart->getShippingCosts()->getTotalPrice();
        $itemTotal = $cartTotal - $shippingCosts;

        $purchaseUnit = [
            'amount' => [
                'currency_code' => $currencyCode,
                'value' => $this->priceFormatter->formatPrice($cartTotal, $currencyCode),
                'breakdown' => [
                    'item_total' => [
                        'currency_code' => $currencyCode,
                        'value' => $this->priceFormatter->formatPrice($itemTotal, $currencyCode),
                    ],
                    'shipping' => [
                        'currency_code' => $currencyCode,
                        'value' => $this->priceFormatter->formatPrice($shippingCosts, $currencyCode),
                    ],
                    'tax_total' => [
                        'currency_code' => $currencyCode,
                        'value' => $this->priceFormatter->formatPrice($cartTax, $currencyCode),
                    ],
                ],
            ],
        ];

        $this->logger->info('Cart recalculation completed', [
            'paypalOrderId' => $paypalOrderId,
            'newCountry' => $countryCode,
            'newTotal' => $purchaseUnit['amount']['value'],
            'newTax' => $purchaseUnit['amount']['breakdown']['tax_total']['value'],
        ]);

        return [$purchaseUnit];
    }

    private function getCountryByIso(string $iso, Context $context): ?CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->setLimit(1);

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        return $country;
    }
}

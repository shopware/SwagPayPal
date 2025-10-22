<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;

#[Package('checkout')]
class ExpressShippingCallbackService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly EntityRepository $countryRepository,
        private readonly AmountProvider $amountProvider,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
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
        $this->logger->debug('Shipping callback: Recalculating cart for address change', [
            'paypalOrderId' => $paypalOrderId,
            'countryCode' => $shippingAddress['country_code'] ?? null,
            'fullAddress' => $shippingAddress,
        ]);

        // Get country by ISO code
        $countryCode = $shippingAddress['country_code'] ?? null;
        if (!$countryCode) {
            $this->logger->error('Shipping callback: Missing country code in shipping address');
            throw CheckoutException::expressMissingCountryCode();
        }

        $country = $this->getCountryByIso($countryCode, $salesChannelContext->getContext());
        if (!$country) {
            $this->logger->error('Country not found', ['countryCode' => $countryCode]);
            throw CheckoutException::expressCountryNotFound($countryCode);
        }

        // Since a new customer was logged in, the context changed in the system,
        // but this doesn't effect the current context given as parameter.
        // Because of that a new context for the cart recalculation is created
        $this->logger->debug('Switching context to new country', ['countryId' => $country->getId()]);
        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([
                SalesChannelContextService::COUNTRY_ID => $country->getId(),
            ]),
            $salesChannelContext
        );

        $newSalesChannelContext = $this->salesChannelContextFactory->create(
            $salesChannelContext->getToken(),
            $salesChannelContext->getSalesChannel()->getId()
        );

        // Recalculate cart with new context
        $this->logger->debug('Recalculating cart with new country context');
        $cart = $this->cartService->recalculate(
            $this->cartService->getCart($newSalesChannelContext->getToken(), $newSalesChannelContext),
            $newSalesChannelContext
        );

        $this->logger->debug('Cart recalculated', [
            'cartTotal' => $cart->getPrice()->getTotalPrice(),
            'cartTax' => $cart->getPrice()->getCalculatedTaxes()->getAmount(),
            'shippingCosts' => $cart->getShippingCosts()->getTotalPrice(),
        ]);

        // Build the response in PayPal's expected format
        $currency = $newSalesChannelContext->getCurrency();
        $totalPrice = new CalculatedPrice(
            $cart->getPrice()->getTotalPrice(),
            $cart->getPrice()->getTotalPrice(),
            $cart->getPrice()->getCalculatedTaxes(),
            $cart->getPrice()->getTaxRules()
        );

        // Create a temporary PurchaseUnit to use AmountProvider
        $purchaseUnit = new PurchaseUnit();
        $amount = $this->amountProvider->createAmount(
            $totalPrice,
            $cart->getShippingCosts(),
            $currency,
            $purchaseUnit,
            $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
        $purchaseUnit->setAmount($amount);

        $purchaseUnitArray = $purchaseUnit->jsonSerialize();

        $this->logger->debug('Cart recalculation completed', [
            'paypalOrderId' => $paypalOrderId,
            'newCountry' => $countryCode,
            'newTotal' => $amount->getValue(),
            'newTax' => $amount->getBreakdown()?->getTaxTotal()?->getValue(),
        ]);

        return [$purchaseUnitArray];
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

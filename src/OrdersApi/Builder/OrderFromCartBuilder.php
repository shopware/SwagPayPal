<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalV2ItemFromCartEvent;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ItemCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
class OrderFromCartBuilder extends AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PriceFormatter $priceFormatter,
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        LocaleCodeProvider $localeCodeProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly VaultTokenService $vaultTokenService,
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider, $localeCodeProvider);
    }

    public function getOrder(
        Cart $cart,
        Request $request,
        SalesChannelContext $salesChannelContext,
        ?CustomerEntity $customer
    ): Order {
        $order = new Order();

        $intent = $this->getIntent($salesChannelContext->getSalesChannelId());
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $cart, $customer);

        $order->setIntent($intent);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $order->setPaymentSource($this->createPaymentSource($salesChannelContext, $request, $customer));

        return $order;
    }

    private function createPaymentSource(
        SalesChannelContext $salesChannelContext,
        Request $request,
        ?CustomerEntity $customer
    ): PaymentSource {
        $paymentSource = new PaymentSource();
        $paypal = new Paypal();
        $paymentSource->setPaypal($paypal);

        $paypal->setExperienceContext($this->createExperienceContext($salesChannelContext));

        if ($customer === null) {
            return $paymentSource;
        }

        $paypal->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $paypal->setName($name);

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }
        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $paypal->setAddress($address);

        if ($salesChannelContext->hasExtension('subscription')) {
            $this->vaultTokenService->requestVaulting($paypal);
        }

        if ($request->request->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $this->vaultTokenService->requestVaulting($paypal);
        }

        return $paymentSource;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
        ?CustomerEntity $customer
    ): PurchaseUnit {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw new InvalidTransactionException('');
        }

        $submitCart = $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());

        $items = $submitCart ? $this->createItems($salesChannelContext->getCurrency(), $cart) : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $cartTransaction->getAmount(),
            $cart->getShippingCosts(),
            $customer,
            $items,
            $salesChannelContext,
            $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
    }

    private function createItems(CurrencyEntity $currency, Cart $cart): ItemCollection
    {
        $items = new ItemCollection();
        $currencyCode = $currency->getIsoCode();

        foreach ($cart->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $item = new Item();
            $this->setName($lineItem, $item);
            $this->setSku($lineItem, $item);

            $tax = new Money();
            $tax->setCurrencyCode($currencyCode);
            $tax->setValue($this->priceFormatter->formatPrice($price->getCalculatedTaxes()->getAmount(), $currencyCode));
            $item->setTax($tax);

            $unitAmount = new Money();
            $unitAmount->setCurrencyCode($currencyCode);
            $unitAmount->setValue($this->priceFormatter->formatPrice($price->getUnitPrice(), $currencyCode));

            $item->setUnitAmount($unitAmount);
            $item->setQuantity($lineItem->getQuantity());

            $event = new PayPalV2ItemFromCartEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items->add($event->getPayPalLineItem());
        }

        return $items;
    }

    private function setName(LineItem $lineItem, Item $item): void
    {
        $label = (string) $lineItem->getLabel();

        try {
            $item->setName($label);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setName(\mb_substr($label, 0, Item::MAX_LENGTH_NAME));
        }
    }

    private function setSku(LineItem $lineItem, Item $item): void
    {
        $productNumber = $lineItem->getPayloadValue('productNumber');

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\mb_substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }
}

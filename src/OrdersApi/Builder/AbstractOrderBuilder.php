<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
abstract class AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        protected readonly SystemConfigService $systemConfigService,
        protected readonly PurchaseUnitProvider $purchaseUnitProvider,
        protected readonly AddressProvider $addressProvider,
        protected readonly LocaleCodeProvider $localeCodeProvider,
        protected readonly ItemListProvider $itemListProvider,
    ) {
    }

    public function getOrder(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
    ): Order {
        $purchaseUnit = $this->createPurchaseUnitFromOrder(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
        );

        $order = new Order();
        $order->setIntent($this->getIntent($salesChannelContext->getSalesChannelId()));
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $paymentSource = new PaymentSource();
        $this->buildPaymentSource($paymentTransaction, $salesChannelContext, $requestDataBag, $paymentSource);
        $order->setPaymentSource($paymentSource);

        return $order;
    }

    public function getOrderFromCart(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
    ): Order {
        $purchaseUnit = $this->createPurchaseUnitFromCart($salesChannelContext, $cart);

        $order = new Order();
        $order->setIntent($this->getIntent($salesChannelContext->getSalesChannelId()));
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $paymentSource = new PaymentSource();
        $this->buildPaymentSourceFromCart($cart, $salesChannelContext, $requestDataBag, $paymentSource);
        $order->setPaymentSource($paymentSource);

        return $order;
    }

    abstract protected function buildPaymentSource(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void;

    abstract protected function buildPaymentSourceFromCart(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void;

    protected function createPurchaseUnitFromOrder(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
    ): PurchaseUnit {
        $items = $this->submitCart($salesChannelContext) ? $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $order) : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            null,
            $items,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS, /* @phpstan-ignore-line */
            $order,
            $orderTransaction
        );
    }

    protected function createPurchaseUnitFromCart(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
    ): PurchaseUnit {
        $cartTransaction = $cart->getTransactions()->first();
        if ($cartTransaction === null) {
            throw PaymentException::invalidTransaction('');
        }

        $items = $this->submitCart($salesChannelContext)
            ? $this->itemListProvider->getItemListFromCart($salesChannelContext->getCurrency(), $cart)
            : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $cartTransaction->getAmount(),
            $cart->getShippingCosts(),
            $salesChannelContext->getCustomer(),
            $items,
            $salesChannelContext,
            $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(string $salesChannelId): string
    {
        $intent = $this->systemConfigService->getString(Settings::INTENT, $salesChannelId);

        if (!\in_array($intent, PaymentIntentV2::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intent');
        }

        return $intent;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed, use payment source attributes instead
     */
    protected function createPayer(CustomerEntity $customer): Payer
    {
        $payer = new Payer();
        $payer->setEmailAddress($customer->getEmail());
        $name = new Name();
        $name->setGivenName($customer->getFirstName());
        $name->setSurname($customer->getLastName());
        $payer->setName($name);

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }
        $address = new Address();
        $this->addressProvider->createAddress($billingAddress, $address);
        $payer->setAddress($address);

        return $payer;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed, use experience context instead
     */
    protected function createApplicationContext(
        SalesChannelContext $salesChannelContext,
    ): ApplicationContext {
        $applicationContext = new ApplicationContext();
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType($salesChannelContext->getSalesChannelId()));

        return $applicationContext;
    }

    /**
     * @deprecated tag:v10.0.0 - parameter $paymentTransaction will be required
     */
    protected function createExperienceContext(
        SalesChannelContext $salesChannelContext,
        SyncPaymentTransactionStruct|Cart|null $paymentTransaction = null,
    ): ExperienceContext {
        $experienceContext = new ExperienceContext();
        $experienceContext->setBrandName($this->getBrandName($salesChannelContext));
        $experienceContext->setLocale($this->localeCodeProvider->getLocaleCodeFromContext($salesChannelContext->getContext()));
        $experienceContext->setLandingPage($this->getLandingPageType($salesChannelContext->getSalesChannelId()));
        $delivery = $paymentTransaction instanceof Cart
            ? $paymentTransaction->getDeliveries()->first()
            : $paymentTransaction?->getOrder()?->getDeliveries()?->first();

        $experienceContext->setShippingPreference($delivery !== null
            ? ExperienceContext::SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS
            : ExperienceContext::SHIPPING_PREFERENCE_NO_SHIPPING);

        if ($paymentTransaction instanceof AsyncPaymentTransactionStruct) {
            $experienceContext->setReturnUrl($paymentTransaction->getReturnUrl());
            $experienceContext->setCancelUrl(\sprintf('%s&cancel=1', $paymentTransaction->getReturnUrl()));
        } else {
            $experienceContext->setReturnUrl(CreateOrderRoute::FAKE_URL);
            $experienceContext->setCancelUrl(CreateOrderRoute::FAKE_URL . '?cancel=1');
        }

        return $experienceContext;
    }

    protected function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelContext->getSalesChannelId());

        if ($brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getTranslation('name') ?? '';
        }

        return $brandName;
    }

    protected function submitCart(SalesChannelContext $salesChannelContext): bool
    {
        return $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function getLandingPageType(string $salesChannelId): string
    {
        $landingPageType = $this->systemConfigService->getString(Settings::LANDING_PAGE, $salesChannelId);

        if (!\in_array($landingPageType, ExperienceContext::LANDING_PAGE_TYPES, true)) {
            throw new PayPalSettingsInvalidException('landingPage');
        }

        return $landingPageType;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\ExperienceContext;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnitCollection;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractOrderBuilder
{
    public const PRELIMINARY_ATTRIBUTE = 'isPayPalPreliminaryOrder';

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
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
    ): Order {
        $purchaseUnit = $this->createPurchaseUnitFromOrder($context, $order, $orderTransaction, $request->attributes->getBoolean(self::PRELIMINARY_ATTRIBUTE));
        $payPalOrder = new Order();
        $payPalOrder->setIntent($this->getIntent($order->getSalesChannelId()));
        $payPalOrder->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $paymentSource = new PaymentSource();
        $this->buildPaymentSource($paymentTransaction, $orderTransaction, $order, $context, $request, $paymentSource);
        $payPalOrder->setPaymentSource($paymentSource);

        return $payPalOrder;
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
        PaymentTransactionStruct $paymentTransaction,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        Context $context,
        Request $request,
        PaymentSource $paymentSource,
    ): void;

    abstract protected function buildPaymentSourceFromCart(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void;

    protected function createPurchaseUnitFromOrder(
        Context $context,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        bool $isPreliminary = false,
    ): PurchaseUnit {
        $currency = $order->getCurrency();
        \assert($currency !== null);
        $items = $this->submitCart($order->getSalesChannelId()) ? $this->itemListProvider->getItemList($currency, $order) : null;
        $taxStatus = $order->getTaxStatus() ?? $order->getPrice()->getTaxStatus();

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            null,
            $items,
            $currency,
            $context,
            $taxStatus !== CartPrice::TAX_STATE_GROSS,
            $order,
            $isPreliminary ? null : $orderTransaction
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

        $items = $this->submitCart($salesChannelContext->getSalesChannelId())
            ? $this->itemListProvider->getItemListFromCart($salesChannelContext->getCurrency(), $cart)
            : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $cartTransaction->getAmount(),
            $cart->getShippingCosts(),
            $salesChannelContext->getCustomer(),
            $items,
            $salesChannelContext->getCurrency(),
            $salesChannelContext->getContext(),
            $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS
        );
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(string $salesChannelId): string
    {
        $intent = $this->systemConfigService->getString(Settings::INTENT, $salesChannelId);

        if (!\in_array($intent, [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::INTENT_CAPTURE], true)) {
            throw new PayPalSettingsInvalidException('intent');
        }

        return $intent;
    }

    protected function createExperienceContext(
        OrderEntity|Cart $orderOrCart,
        SalesChannelEntity $salesChannel,
        Context $context,
        ?PaymentTransactionStruct $paymentTransaction = null,
    ): ExperienceContext {
        $experienceContext = new ExperienceContext();
        $experienceContext->setBrandName($this->getBrandName($salesChannel));
        $experienceContext->setLocale($this->localeCodeProvider->getLocaleCodeFromContext($context));
        $experienceContext->setLandingPage($this->getLandingPageType($salesChannel->getId()));
        $delivery = $orderOrCart instanceof Cart
            ? $orderOrCart->getDeliveries()->first()
            : $orderOrCart->getDeliveries()?->first();

        $experienceContext->setShippingPreference($delivery !== null
            ? ExperienceContext::SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS
            : ExperienceContext::SHIPPING_PREFERENCE_NO_SHIPPING);

        if ($paymentTransaction?->getReturnUrl()) {
            $experienceContext->setReturnUrl($paymentTransaction->getReturnUrl());
            $experienceContext->setCancelUrl(\sprintf('%s&cancel=1', $paymentTransaction->getReturnUrl()));
        } else {
            $experienceContext->setReturnUrl(CreateOrderRoute::FAKE_URL);
            $experienceContext->setCancelUrl(CreateOrderRoute::FAKE_URL . '?cancel=1');
        }

        return $experienceContext;
    }

    protected function getBrandName(SalesChannelEntity $salesChannel): string
    {
        $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannel->getId());

        if ($brandName === '') {
            $brandName = $salesChannel->getTranslation('name') ?? '';
        }

        return $brandName;
    }

    protected function submitCart(string $salesChannelId): bool
    {
        return $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelId);
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

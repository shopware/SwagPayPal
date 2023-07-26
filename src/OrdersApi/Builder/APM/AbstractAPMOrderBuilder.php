<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractAPMPaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
abstract class AbstractAPMOrderBuilder extends AbstractOrderBuilder
{
    private ItemListProvider $itemListProvider;

    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        LocaleCodeProvider $localeCodeProvider,
        ItemListProvider $itemListProvider,
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider, $localeCodeProvider);
        $this->itemListProvider = $itemListProvider;
    }

    public function getOrder(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag
    ): Order {
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
        );

        $order = new Order();
        $order->setIntent(PaymentIntentV2::CAPTURE);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $paymentSource = new PaymentSource();
        $this->buildPaymentSource($paymentTransaction, $salesChannelContext, $requestDataBag, $paymentSource);
        $order->setPaymentSource($paymentSource);
        $order->setProcessingInstruction(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL);

        return $order;
    }

    abstract protected function buildPaymentSource(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void;

    protected function fillPaymentSource(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        AbstractAPMPaymentSource $paymentSource
    ): void {
        $address = $paymentTransaction->getOrder()->getBillingAddress();
        if ($address === null) {
            throw new AddressNotFoundException($paymentTransaction->getOrder()->getBillingAddressId());
        }

        $paymentSource->setName(\sprintf('%s %s', $address->getFirstName(), $address->getLastName()));

        $country = $address->getCountry();
        if ($country === null || ($iso = $country->getIso()) === null) {
            throw new AddressNotFoundException($paymentTransaction->getOrder()->getBillingAddressId());
        }

        $paymentSource->setCountryCode($iso);

        $experienceContext = $this->createExperienceContext($salesChannelContext);
        $experienceContext->setReturnUrl($paymentTransaction->getReturnUrl());
        $experienceContext->setCancelUrl(\sprintf('%s&cancel=1', $paymentTransaction->getReturnUrl()));
        $paymentSource->setExperienceContext($experienceContext);
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction
    ): PurchaseUnit {
        $submitCart = $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());

        $items = $submitCart ? $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $order) : null;

        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            null,
            $items,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS,
            $order,
            $orderTransaction
        );

        if (!$purchaseUnit->isset('shipping')) {
            throw new MissingPayloadException('created', 'purchaseUnit.shipping');
        }

        return $purchaseUnit;
    }
}

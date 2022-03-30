<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractAPMPaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Settings;

abstract class AbstractAPMOrderBuilder extends AbstractOrderBuilder
{
    private ItemListProvider $itemListProvider;

    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        ItemListProvider $itemListProvider
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider);
        $this->itemListProvider = $itemListProvider;
    }

    public function getOrder(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer,
        RequestDataBag $requestDataBag
    ): Order {
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
            $customer
        );
        $applicationContext = $this->createApplicationContext($salesChannelContext);
        $this->addReturnUrls($applicationContext, $paymentTransaction->getReturnUrl());

        $order = new Order();
        $order->setIntent(PaymentIntentV2::CAPTURE);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);
        $paymentSource = new PaymentSource();
        $this->buildPaymentSource($paymentTransaction, $salesChannelContext, $requestDataBag, $paymentSource);
        $order->setPaymentSource($paymentSource);

        if ($this->isCompleteOnApproval()) {
            $order->setProcessingInstruction(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL);
        }

        return $order;
    }

    public function isCompleteOnApproval(): bool
    {
        return false;
    }

    abstract protected function buildPaymentSource(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource
    ): void;

    protected function fillPaymentSource(
        OrderEntity $order,
        AbstractAPMPaymentSource $paymentSource
    ): void {
        $address = $order->getBillingAddress();
        if ($address === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        $paymentSource->setName(\sprintf('%s %s', $address->getFirstName(), $address->getLastName()));

        $country = $address->getCountry();
        if ($country === null || ($iso = $country->getIso()) === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        $paymentSource->setCountryCode($iso);
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        CustomerEntity $customer
    ): PurchaseUnit {
        $submitCart = $this->systemConfigService->getBool(Settings::SUBMIT_CART, $salesChannelContext->getSalesChannelId());

        $items = $submitCart ? $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $order) : null;

        return $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $customer,
            $items,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS,
            $order,
            $orderTransaction
        );
    }

    private function addReturnUrls(ApplicationContext $applicationContext, string $returnUrl): void
    {
        $applicationContext->setReturnUrl($returnUrl);
        $applicationContext->setCancelUrl(\sprintf('%s&cancel=1', $returnUrl));
    }
}

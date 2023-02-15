<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\Setting\Settings;

class OrderFromOrderBuilder extends AbstractOrderBuilder
{
    private ItemListProvider $itemListProvider;

    /**
     * @internal
     */
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
        CustomerEntity $customer
    ): Order {
        $intent = $this->getIntent($salesChannelContext->getSalesChannelId());
        $payer = $this->createPayer($customer);
        $purchaseUnit = $this->createPurchaseUnit(
            $salesChannelContext,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction(),
            $customer
        );
        $applicationContext = $this->createApplicationContext($salesChannelContext);
        $this->addReturnUrls($applicationContext, $paymentTransaction->getReturnUrl());

        $order = new Order();
        $order->setIntent($intent);
        $order->setPayer($payer);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
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

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Patch;

class PurchaseUnitPatchBuilder
{
    private PurchaseUnitProvider $purchaseUnitProvider;

    private ItemListProvider $itemListProvider;

    public function __construct(
        PurchaseUnitProvider $purchaseUnitProvider,
        ItemListProvider $itemListProvider
    ) {
        $this->purchaseUnitProvider = $purchaseUnitProvider;
        $this->itemListProvider = $itemListProvider;
    }

    /**
     * @param Item[]|null $itemList
     *
     * @deprecated tag:v6.0.0 - will be removed, use createFinalPurchaseUnitPatch() instead
     */
    public function createPurchaseUnitPatch(
        CustomerEntity $customer,
        ?array $itemList,
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction
    ): Patch {
        return $this->createPatch($orderTransaction, $order, $customer, $itemList, $salesChannelContext);
    }

    public function createFinalPurchaseUnitPatch(
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        SalesChannelContext $salesChannelContext,
        bool $submitCart = true
    ): Patch {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new CustomerNotLoggedInException();
        }

        if ($submitCart) {
            $itemList = $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $order);
        } else {
            $itemList = null;
        }

        return $this->createPatch($orderTransaction, $order, $customer, $itemList, $salesChannelContext);
    }

    private function createPatch(OrderTransactionEntity $orderTransaction, OrderEntity $order, CustomerEntity $customer, ?array $itemList, SalesChannelContext $salesChannelContext): Patch
    {
        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $customer,
            $itemList,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS,
            $order,
            $orderTransaction
        );
        $purchaseUnitArray = \json_decode((string) \json_encode($purchaseUnit), true);

        $purchaseUnitPatch = new Patch();
        $purchaseUnitPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/purchase_units/@reference_id==\'default\'',
        ]);
        $purchaseUnitPatch->setValue($purchaseUnitArray);

        return $purchaseUnitPatch;
    }
}

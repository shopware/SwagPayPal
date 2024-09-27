<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Patch;

#[Package('checkout')]
class PurchaseUnitPatchBuilder
{
    private PurchaseUnitProvider $purchaseUnitProvider;

    private ItemListProvider $itemListProvider;

    /**
     * @internal
     */
    public function __construct(
        PurchaseUnitProvider $purchaseUnitProvider,
        ItemListProvider $itemListProvider,
    ) {
        $this->purchaseUnitProvider = $purchaseUnitProvider;
        $this->itemListProvider = $itemListProvider;
    }

    public function createFinalPurchaseUnitPatch(
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        SalesChannelContext $salesChannelContext,
        bool $submitCart = true,
    ): Patch {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        if ($submitCart) {
            $itemList = $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $order);
        } else {
            $itemList = null;
        }

        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $customer,
            $itemList,
            $salesChannelContext,
            $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS, /* @phpstan-ignore-line */
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

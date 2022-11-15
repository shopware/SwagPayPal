<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Patch;

class PurchaseUnitPatchBuilder
{
    private PurchaseUnitProvider $purchaseUnitProvider;

    public function __construct(PurchaseUnitProvider $purchaseUnitProvider)
    {
        $this->purchaseUnitProvider = $purchaseUnitProvider;
    }

    /**
     * @param Item[]|null $itemList
     */
    public function createPurchaseUnitPatch(
        CustomerEntity $customer,
        ?array $itemList,
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction
    ): Patch {
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

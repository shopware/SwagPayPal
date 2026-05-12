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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Patch;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;

#[Package('checkout')]
class PurchaseUnitPatchBuilder
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PurchaseUnitProvider $purchaseUnitProvider,
        private readonly ItemListProvider $itemListProvider,
    ) {
    }

    public function createFinalPurchaseUnitPatch(
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        Context $context,
        bool $submitCart = true,
    ): Patch {
        $customer = $order->getOrderCustomer()?->getCustomer();
        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $currency = $order->getCurrency();
        \assert($currency !== null);
        if ($submitCart) {
            $itemList = $this->itemListProvider->getItemList($currency, $order);
        } else {
            $itemList = null;
        }

        $taxStatus = $order->getTaxStatus() ?? $order->getPrice()->getTaxStatus();

        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $orderTransaction->getAmount(),
            $order->getShippingCosts(),
            $customer,
            $itemList,
            $currency,
            $context,
            $taxStatus !== CartPrice::TAX_STATE_GROSS,
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

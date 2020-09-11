<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Order\OrderEntity;
use Swag\PayPal\PaymentsApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\Util\PriceFormatter;

class ItemListPatchBuilder
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function createItemListPatch(OrderEntity $order, string $currency): Patch
    {
        $itemList = (new ItemListProvider($this->priceFormatter))->getItemList($order, $currency);
        $itemListArray = \json_decode((string) \json_encode($itemList), true);

        $itemListPatch = new Patch();
        $itemListPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/transactions/0/item_list/items',
        ]);
        $itemListPatch->setValue($itemListArray);

        return $itemListPatch;
    }
}

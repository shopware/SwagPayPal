<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Item;

/**
 * @internal
 */
#[Package('checkout')]
final class DigitalGoodsCategoryRemover
{
    // PayPal returns no shipping address for orders with only digital goods, but the customer cannot be registered without one
    public static function remove(Order $order): void
    {
        foreach ($order->getPurchaseUnits() as $purchaseUnit) {
            foreach ($purchaseUnit->getItems() ?? [] as $item) {
                if ($item->isset('category') && $item->getCategory() === Item::CATEGORY_DIGITAL_GOODS) {
                    $item->unset('category');
                }
            }
        }
    }
}

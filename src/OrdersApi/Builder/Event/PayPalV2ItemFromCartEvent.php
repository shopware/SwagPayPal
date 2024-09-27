<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Event;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Use this event to adjust the items of the cart which will be submitted to PayPal
 */
#[Package('checkout')]
class PayPalV2ItemFromCartEvent extends Event
{
    private Item $payPalLineItem;

    private LineItem $originalShopwareLineItem;

    public function __construct(
        Item $payPalLineItem,
        LineItem $originalShopwareLineItem,
    ) {
        $this->payPalLineItem = $payPalLineItem;
        $this->originalShopwareLineItem = $originalShopwareLineItem;
    }

    public function getPayPalLineItem(): Item
    {
        return $this->payPalLineItem;
    }

    public function getOriginalShopwareLineItem(): LineItem
    {
        return $this->originalShopwareLineItem;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Event;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Use this event to adjust the items of the order which will be submitted to PayPal
 */
#[Package('checkout')]
class PayPalV2ItemFromOrderEvent extends Event
{
    private Item $payPalLineItem;

    private OrderLineItemEntity $originalShopwareLineItem;

    public function __construct(
        Item $payPalLineItem,
        OrderLineItemEntity $originalShopwareLineItem,
    ) {
        $this->payPalLineItem = $payPalLineItem;
        $this->originalShopwareLineItem = $originalShopwareLineItem;
    }

    public function getPayPalLineItem(): Item
    {
        return $this->payPalLineItem;
    }

    public function getOriginalShopwareLineItem(): OrderLineItemEntity
    {
        return $this->originalShopwareLineItem;
    }
}

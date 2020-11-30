<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Event;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Use this event to adjust the items of the order which will be submitted to PayPal
 */
class PayPalItemFromOrderEvent extends Event
{
    /**
     * @var Item
     */
    private $paypalLineItem;

    /**
     * @var OrderLineItemEntity
     */
    private $originalShopwareLineItem;

    public function __construct(Item $paypalLineItem, OrderLineItemEntity $originalShopwareLineItem)
    {
        $this->paypalLineItem = $paypalLineItem;
        $this->originalShopwareLineItem = $originalShopwareLineItem;
    }

    public function getPaypalLineItem(): Item
    {
        return $this->paypalLineItem;
    }

    public function getOriginalShopwareLineItem(): OrderLineItemEntity
    {
        return $this->originalShopwareLineItem;
    }
}

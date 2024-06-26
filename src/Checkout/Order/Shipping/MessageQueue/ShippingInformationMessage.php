<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping\MessageQueue;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;

#[Package('checkout')]
class ShippingInformationMessage implements AsyncMessageInterface, \JsonSerializable
{
    use JsonSerializableTrait;

    public function __construct(
        protected string $orderDeliveryId,
    ) {
    }

    public function getOrderDeliveryId(): string
    {
        return $this->orderDeliveryId;
    }
}

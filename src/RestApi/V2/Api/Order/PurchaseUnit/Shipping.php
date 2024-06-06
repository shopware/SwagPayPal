<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Tracker;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\TrackerCollection;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_shipping')]
#[Package('checkout')]
class Shipping extends PayPalApiStruct
{
    #[OA\Property(ref: Name::class)]
    protected Name $name;

    #[OA\Property(ref: Address::class)]
    protected Address $address;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Tracker::class), nullable: true)]
    protected ?TrackerCollection $trackers = null;

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getTrackers(): ?TrackerCollection
    {
        return $this->trackers;
    }

    public function setTrackers(?TrackerCollection $trackers): void
    {
        $this->trackers = $trackers;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_shipping")
 */
class Shipping extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Name
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_shipping_name")
     */
    protected $name;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Address
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_address")
     */
    protected $address;

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
}

<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOption;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;

#[OA\Schema(schema: 'swag_paypal_v2_order_shipping_callback')]
#[Package('checkout')]
class OrderShippingCallback extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(ref: Address::class)]
    protected Address $shippingAddress;

    #[OA\Property(ref: ShippingOption::class)]
    protected ?ShippingOption $shippingOption = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: PurchaseUnit::class))]
    protected PurchaseUnitCollection $purchaseUnits;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getShippingOption(): ?ShippingOption
    {
        return $this->shippingOption;
    }

    public function setShippingOption(?ShippingOption $shippingOption): void
    {
        $this->shippingOption = $shippingOption;
    }

    public function getPurchaseUnits(): PurchaseUnitCollection
    {
        return $this->purchaseUnits;
    }

    public function setPurchaseUnits(PurchaseUnitCollection $purchaseUnits): void
    {
        $this->purchaseUnits = $purchaseUnits;
    }
}

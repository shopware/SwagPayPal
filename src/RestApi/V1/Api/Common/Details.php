<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_common_details')]
#[Package('checkout')]
class Details extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $subtotal;

    #[OA\Property(type: 'string')]
    protected string $shipping;

    #[OA\Property(type: 'string')]
    protected string $tax;

    #[OA\Property(type: 'string')]
    protected string $handlingFee;

    #[OA\Property(type: 'string')]
    protected string $shippingDiscount;

    #[OA\Property(type: 'string')]
    protected string $discount;

    #[OA\Property(type: 'string')]
    protected string $insurance;

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function getShipping(): string
    {
        return $this->shipping;
    }

    public function setShipping(string $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }

    public function getHandlingFee(): string
    {
        return $this->handlingFee;
    }

    public function setHandlingFee(string $handlingFee): void
    {
        $this->handlingFee = $handlingFee;
    }

    public function getShippingDiscount(): string
    {
        return $this->shippingDiscount;
    }

    public function setShippingDiscount(string $shippingDiscount): void
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function setDiscount(string $discount): void
    {
        $this->discount = $discount;
    }

    public function getInsurance(): string
    {
        return $this->insurance;
    }

    public function setInsurance(string $insurance): void
    {
        $this->insurance = $insurance;
    }
}

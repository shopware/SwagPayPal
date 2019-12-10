<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Common;

abstract class Details extends PayPalStruct
{
    /**
     * @var string
     */
    protected $subtotal;

    /**
     * @var string
     */
    protected $shipping;

    /**
     * @var string
     */
    protected $tax;

    /**
     * @var string
     */
    protected $handlingFee;

    /**
     * @var string
     */
    protected $shippingDiscount;

    /**
     * @var string
     */
    protected $insurance;

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function getShipping(): string
    {
        return $this->shipping;
    }

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setSubtotal(string $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function setShipping(string $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }

    protected function setHandlingFee(string $handlingFee): void
    {
        $this->handlingFee = $handlingFee;
    }

    protected function setShippingDiscount(string $shippingDiscount): void
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    protected function setInsurance(string $insurance): void
    {
        $this->insurance = $insurance;
    }
}

<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Common;

use SwagPayPal\PayPal\Api\PayPalStruct;

class Details extends PayPalStruct
{
    /**
     * @var string
     */
    protected $shipping;

    /**
     * @var string
     */
    protected $subtotal;

    /**
     * @var string
     */
    protected $tax;

    public function setShipping(string $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function setSubtotal(string $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }
}

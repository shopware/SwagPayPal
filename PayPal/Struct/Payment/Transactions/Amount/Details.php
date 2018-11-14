<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;

class Details
{
    /**
     * @var float
     */
    private $shipping;

    /**
     * @var float
     */
    private $subTotal;

    /**
     * @var float
     */
    private $tax;

    public function getShipping(): float
    {
        return $this->shipping;
    }

    public function setShipping(float $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getSubTotal(): float
    {
        return $this->subTotal;
    }

    public function setSubTotal(float $subTotal): void
    {
        $this->subTotal = $subTotal;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function setTax(float $tax): void
    {
        $this->tax = $tax;
    }

    /**
     * @param array $data
     *
     * @return Details
     */
    public static function fromArray(array $data = []): Details
    {
        $result = new self();

        if (array_key_exists('shipping', $data)) {
            $result->setShipping((float) $data['shipping']);
        }
        if (array_key_exists('tax', $data)) {
            $result->setTax((float) $data['tax']);
        }
        if (array_key_exists('subtotal', $data)) {
            $result->setSubTotal((float) $data['subtotal']);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'shipping' => $this->getShipping(),
            'subtotal' => $this->getSubTotal(),
            'tax' => $this->getTax(),
        ];
    }
}

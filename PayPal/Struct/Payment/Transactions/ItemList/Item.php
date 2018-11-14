<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList;

class Item
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $price;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var string
     */
    private $tax;

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public static function fromArray(array $data): Item
    {
        $result = new self();

        $result->setName($data['name']);
        $result->setSku($data['sku']);
        $result->setPrice((float) $data['price']);
        $result->setCurrency($data['currency']);
        $result->setTax($data['tax']);
        $result->setQuantity((int) $data['quantity']);

        return $result;
    }

    public function toArray(): array
    {
        //We don't work with taxes in this case to avoid calculation errors.
        return [
            'name' => $this->getName(),
            'sku' => $this->getSku(),
            'price' => $this->getPrice(),
            'currency' => $this->getCurrency(),
            'quantity' => $this->getQuantity(),
        ];
    }
}

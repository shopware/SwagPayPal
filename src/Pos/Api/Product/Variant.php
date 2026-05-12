<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Product;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\Variant\CostPrice;
use Swag\PayPal\Pos\Api\Product\Variant\Option;
use Swag\PayPal\Pos\Api\Product\Variant\Price;

#[Package('checkout')]
class Variant extends PosStruct
{
    protected string $uuid;

    protected string $name;

    protected string $description;

    protected string $sku;

    protected string $barcode;

    protected Price $price;

    protected CostPrice $costPrice;

    /**
     * @var Option[]|null
     */
    protected ?array $options = null;

    protected Presentation $presentation;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function setPrice(Price $price): void
    {
        $this->price = $price;
    }

    public function getCostPrice(): CostPrice
    {
        return $this->costPrice;
    }

    public function setCostPrice(CostPrice $costPrice): void
    {
        $this->costPrice = $costPrice;
    }

    /**
     * @return Option[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param Option[]|null $options
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    public function addOption(Option ...$options): void
    {
        $this->options = \array_merge($this->options ?? [], $options);
    }

    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        if ($data['price'] === null) {
            unset($data['price']);
        }

        return $data;
    }
}

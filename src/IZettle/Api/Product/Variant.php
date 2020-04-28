<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Product;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Product\Variant\Option;
use Swag\PayPal\IZettle\Api\Product\Variant\Presentation as VariantPresentation;

class Variant extends IZettleStruct
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $sku;

    /**
     * @var string
     */
    protected $barcode;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var ?Option[]
     */
    protected $options;

    /**
     * @var VariantPresentation
     */
    protected $presentation;

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function setPrice(Price $price): void
    {
        $this->price = $price;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param Option[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function addOption(Option ...$options): void
    {
        $this->options = \array_merge($this->options ?? [], $options);
    }

    public function setPresentation(VariantPresentation $presentation): void
    {
        $this->presentation = $presentation;
    }
}

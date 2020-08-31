<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api;

use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\Category;
use Swag\PayPal\Pos\Api\Product\Presentation;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;

class Product extends PosStruct
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
     * @var Category
     */
    protected $category;

    /**
     * @var Variant[]
     */
    protected $variants = [];

    /**
     * @var ?VariantOptionDefinitions
     */
    protected $variantOptionDefinitions;

    /**
     * @var float
     */
    protected $vatPercentage;

    /**
     * @var Presentation
     */
    protected $presentation;

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

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function addVariant(Variant ...$variants): void
    {
        $this->variants = \array_merge($this->variants, $variants);
    }

    public function getVariantOptionDefinitions(): ?VariantOptionDefinitions
    {
        return $this->variantOptionDefinitions;
    }

    public function setVariantOptionDefinitions(?VariantOptionDefinitions $variantOptionDefinitions): void
    {
        $this->variantOptionDefinitions = $variantOptionDefinitions;
    }

    /**
     * @param string|float $vatPercentage
     */
    public function setVatPercentage($vatPercentage): void
    {
        $this->vatPercentage = (float) $vatPercentage;
    }

    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    /**
     * Generates a unique checksum for the converted product to recognize changes on repeated syncs.
     */
    public function generateChecksum(): string
    {
        return \md5(\serialize($this));
    }
}

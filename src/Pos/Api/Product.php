<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Product\Category;
use Swag\PayPal\Pos\Api\Product\Metadata;
use Swag\PayPal\Pos\Api\Product\Presentation;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;

#[Package('checkout')]
class Product extends PosStruct
{
    protected string $uuid;

    protected string $name;

    protected string $description;

    protected Category $category;

    /**
     * @var Variant[]
     */
    protected array $variants = [];

    protected ?VariantOptionDefinitions $variantOptionDefinitions = null;

    protected float $vatPercentage;

    protected Presentation $presentation;

    protected Metadata $metadata;

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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @param Variant[] $variants
     */
    public function setVariants(array $variants): void
    {
        $this->variants = $variants;
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

    public function getVatPercentage(): float
    {
        return $this->vatPercentage;
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

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Generates a unique checksum for the converted product to recognize changes on repeated syncs.
     */
    public function generateChecksum(): string
    {
        return \md5(\serialize($this));
    }
}

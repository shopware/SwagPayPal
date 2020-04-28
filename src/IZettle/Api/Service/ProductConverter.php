<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\VariantConverter;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGrouping;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;

class ProductConverter
{
    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    /**
     * @var CategoryConverter
     */
    private $categoryConverter;

    /**
     * @var VariantConverter
     */
    private $variantConverter;

    /**
     * @var OptionGroupConverter
     */
    private $optionGroupConverter;

    public function __construct(
        UuidConverter $uuidConverter,
        CategoryConverter $categoryConverter,
        VariantConverter $variantConverter,
        OptionGroupConverter $optionGroupConverter
    ) {
        $this->uuidConverter = $uuidConverter;
        $this->categoryConverter = $categoryConverter;
        $this->variantConverter = $variantConverter;
        $this->optionGroupConverter = $optionGroupConverter;
    }

    public function convertShopwareProducts(ProductCollection $shopwareProducts, ?CurrencyEntity $currency): ProductGroupingCollection
    {
        $groupingCollection = new ProductGroupingCollection();
        $groupingCollection->addProducts($shopwareProducts);

        foreach ($groupingCollection as $grouping) {
            $product = $this->convertProductGrouping($grouping, $currency);
            $grouping->setProduct($product);
        }

        return $groupingCollection;
    }

    protected function convertProductGrouping(ProductGrouping $productGrouping, ?CurrencyEntity $currency): Product
    {
        $shopwareProduct = $productGrouping->getIdentifyingEntity();

        $mainProductId = $this->uuidConverter->convertUuidToV1($shopwareProduct->getId());

        $product = new Product();
        $product->setUuid($mainProductId);
        $product->setName((string) ($shopwareProduct->getTranslation('name') ?? $shopwareProduct->getName()));
        $product->setDescription((string) ($shopwareProduct->getTranslation('description') ?? $shopwareProduct->getDescription()));

        $tax = $shopwareProduct->getTax();
        if ($tax !== null) {
            $product->setVatPercentage($tax->getTaxRate());
        }

        $categories = $shopwareProduct->getCategories();
        if ($categories !== null) {
            $category = $categories->first();
            if ($category !== null) {
                $product->setCategory($this->categoryConverter->convert($category));
            }
        }

        $configuratorSettings = $shopwareProduct->getConfiguratorSettings();
        if ($configuratorSettings && $configuratorSettings->count()) {
            $product->setVariantOptionDefinitions($this->optionGroupConverter->convert($configuratorSettings->getGroupedOptions()));
        }

        foreach ($productGrouping->getVariantEntities() as $shopwareVariant) {
            $product->addVariant($this->variantConverter->convert($shopwareVariant, $currency));
        }

        if ($product->getVariantOptionDefinitions() === null
            && $product->getVariants() !== null
            && count($product->getVariants()) > 1) {
            $product->setVariantOptionDefinitions($this->optionGroupConverter->convertFromVariants(...$product->getVariants()));
        }

        if (!$product->getVariants()) {
            $product->addVariant($this->variantConverter->convert($shopwareProduct, $currency));
        }

        return $product;
    }
}

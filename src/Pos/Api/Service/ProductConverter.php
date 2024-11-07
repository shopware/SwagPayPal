<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Pos\Api\Exception\PosConversionException;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions\Definition;
use Swag\PayPal\Pos\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\Pos\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\Converter\VariantConverter;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGrouping;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGroupingCollection;

#[Package('checkout')]
class ProductConverter
{
    /**
     * @internal
     */
    public function __construct(
        private readonly UuidConverter $uuidConverter,
        private readonly CategoryConverter $categoryConverter,
        private readonly VariantConverter $variantConverter,
        private readonly OptionGroupConverter $optionGroupConverter,
        private readonly PresentationConverter $presentationConverter,
        private readonly MetadataGenerator $metadataGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param ProductCollection $shopwareProducts containing SalesChannelProductEntity
     */
    public function convertShopwareProducts(ProductCollection $shopwareProducts, ?CurrencyEntity $currency, ProductContext $productContext): ProductGroupingCollection
    {
        $groupingCollection = new ProductGroupingCollection();
        $groupingCollection->addProducts($shopwareProducts);

        foreach ($groupingCollection as $grouping) {
            try {
                $product = $this->convertProductGrouping($grouping, $currency, $productContext);
                $grouping->setProduct($product);
            } catch (PosConversionException $e) {
                $groupingCollection->remove($grouping->getIdentifyingId());
                $this->logger->warning($e, ['product' => $grouping->getIdentifyingEntity()]);
            }
        }

        return $groupingCollection;
    }

    /**
     * @deprecated tag:v10.0.0 - will be private
     */
    protected function convertProductGrouping(ProductGrouping $productGrouping, ?CurrencyEntity $currency, ProductContext $productContext): Product
    {
        $shopwareProduct = $productGrouping->getIdentifyingEntity();

        $mainProductId = $this->uuidConverter->convertUuidToV1($shopwareProduct->getId());

        $product = new Product();
        $product->setUuid($mainProductId);
        $product->setName((string) ($shopwareProduct->getTranslation('name') ?? $shopwareProduct->getName()));
        $product->setDescription((string) ($shopwareProduct->getTranslation('description') ?? $shopwareProduct->getDescription()));
        if (\mb_strlen($product->getDescription()) > 1024) {
            $product->setDescription(\sprintf('%s...', \mb_substr($product->getDescription(), 0, 1021)));
            // no warning to produce, since it will also be added in VariantConverter
        }

        $tax = $shopwareProduct->getCalculatedPrice()->getTaxRules()->first();
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

        $presentation = $this->presentationConverter->convert($shopwareProduct->getCover(), $productContext);
        if ($presentation !== null) {
            $product->setPresentation($presentation);
        }

        foreach ($productGrouping->getVariantEntities() as $shopwareVariant) {
            $product->addVariant($this->variantConverter->convert($shopwareVariant, $currency, $productContext));
        }

        if (\count($product->getVariants()) > 1) {
            $configuratorSettings = $shopwareProduct->getConfiguratorSettings();
            if ($configuratorSettings && $configuratorSettings->count()) {
                $product->setVariantOptionDefinitions($this->optionGroupConverter->convert($configuratorSettings->getGroupedOptions()));
            } else {
                $product->setVariantOptionDefinitions($this->optionGroupConverter->convertFromVariants(...$product->getVariants()));
            }
        }

        if (\count($product->getVariants()) === 0) {
            $product->addVariant($this->variantConverter->convert($shopwareProduct, $currency, $productContext));
        }

        if ($this->hasInvalidVariants($product)) {
            throw new PosConversionException('product', 'Count of variants does not match configurator options');
        }

        $product->setMetadata($this->metadataGenerator->generate());

        return $product;
    }

    private function hasInvalidVariants(Product $product): bool
    {
        $validVariantCount = 1;
        /** @var Definition[] $definitions */
        $definitions = $product->getVariantOptionDefinitions()?->getDefinitions() ?? [];
        foreach ($definitions as $definition) {
            $validVariantCount *= \count($definition->getProperties());
        }

        return $validVariantCount !== \count($product->getVariants());
    }
}

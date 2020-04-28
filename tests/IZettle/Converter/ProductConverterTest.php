<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Product\Category;
use Swag\PayPal\IZettle\Api\Product\Variant;
use Swag\PayPal\IZettle\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\IZettle\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\VariantConverter;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;

class ProductConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_PRICE_CONVERTED = 1111;
    private const TRANSLATION_MARK = '_t';

    public function testConvertMinimal(): void
    {
        $productEntity = $this->createProductEntity();

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency()
        );

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));

        static::assertNotNull($converted->first());
        static::assertEquals($product, $converted->first()->getProduct());
    }

    public function testConvertMaximal(): void
    {
        $productEntity = $this->createProductEntity();
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setCategories(new CategoryCollection([$this->getCategory()]));
        $productEntity->setTax($this->getTax());

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency()
        );

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));
        $product->setName(self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $product->setDescription(self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $product->setCategory(new Category());
        $product->setVatPercentage($this->getTax()->getTaxRate());

        static::assertNotNull($converted->first());
        static::assertEquals($product, $converted->first()->getProduct());
    }

    public function testConvertWithConfiguratorSettings(): void
    {
        $productEntity = $this->createProductEntity();
        $productConfiguratorSettingEntity = new ProductConfiguratorSettingEntity();
        $productConfiguratorSettingEntity->setId(Uuid::randomHex());
        $propertyGroupOptionEntity = new PropertyGroupOptionEntity();
        $propertyGroupOptionEntity->setId(Uuid::randomHex());
        $propertyGroupOptionEntity->setGroupId(Uuid::randomHex());
        $propertyGroupEntity = new PropertyGroupEntity();
        $propertyGroupEntity->setId(Uuid::randomHex());
        $propertyGroupOptionEntity->setGroup($propertyGroupEntity);
        $productConfiguratorSettingEntity->setOption($propertyGroupOptionEntity);
        $productConfiguratorSettingCollection = new ProductConfiguratorSettingCollection([$productConfiguratorSettingEntity]);
        $productEntity->setConfiguratorSettings($productConfiguratorSettingCollection);

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency()
        );

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));
        $product->setVariantOptionDefinitions(new VariantOptionDefinitions());

        static::assertNotNull($converted->first());
        static::assertEquals($product, $converted->first()->getProduct());
    }

    public function testConvertWithVariantOptionDefinitions(): void
    {
        $productEntityParent = $this->createProductEntity();
        $productEntityChild1 = $this->createProductEntity();
        $productEntityChild1->setParentId($productEntityParent->getId());
        $productEntityChild2 = $this->createProductEntity();
        $productEntityChild2->setParentId($productEntityParent->getId());

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntityParent, $productEntityChild1, $productEntityChild2]),
            $this->getCurrency()
        );

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntityParent->getId()));
        $product->setVariantOptionDefinitions(new VariantOptionDefinitions());
        $product->addVariant(new Variant());

        static::assertNotNull($converted->first());
        static::assertEquals($product, $converted->first()->getProduct());
    }

    public function testConvertWithNoCurrency(): void
    {
        $productEntity = $this->createProductEntity();

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            null
        );

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));

        static::assertNotNull($converted->first());
        static::assertEquals($product, $converted->first()->getProduct());
    }

    private function createProductEntity(): ProductEntity
    {
        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setName(self::PRODUCT_NAME);
        $productEntity->setDescription(self::PRODUCT_DESCRIPTION);
        $productEntity->setProductNumber(self::PRODUCT_NUMBER);
        $price = new Price(Defaults::CURRENCY, self::PRODUCT_PRICE, self::PRODUCT_PRICE * 1.19, false);
        $productEntity->setPrice(new PriceCollection([$price]));

        return $productEntity;
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setName(self::PRODUCT_NAME);
        $product->setDescription(self::PRODUCT_DESCRIPTION);
        $product->addVariant(new Variant());

        return $product;
    }

    private function createProductConverter(): ProductConverter
    {
        $variantConverter = $this->createStub(VariantConverter::class);
        $variantConverter->method('convert')->willReturn(new Variant());

        $categoryConverter = $this->createStub(CategoryConverter::class);
        $categoryConverter->method('convert')->willReturn(new Category());

        $optionGroupConverter = $this->createStub(OptionGroupConverter::class);
        $optionGroupConverter->method('convert')->willReturn(new VariantOptionDefinitions());
        $optionGroupConverter->method('convertFromVariants')->willReturn(new VariantOptionDefinitions());

        return new ProductConverter(
            $this->createUuidConverter(),
            $categoryConverter,
            $variantConverter,
            $optionGroupConverter
        );
    }

    private function createUuidConverter(): UuidConverter
    {
        return new UuidConverter();
    }

    private function getCurrency(): CurrencyEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::CURRENCY]);

        return $this->getContainer()->get('currency.repository')->search($criteria, Context::createDefaultContext())->first();
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();

        return $this->getContainer()->get('tax.repository')->search($criteria, Context::createDefaultContext())->first();
    }

    private function getCategory(): CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translation');

        return $this->getContainer()->get('category.repository')->search($criteria, Context::createDefaultContext())->first();
    }
}

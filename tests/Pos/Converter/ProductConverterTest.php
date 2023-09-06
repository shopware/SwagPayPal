<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Api\Product\Category;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\Pos\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\Pos\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\Converter\VariantConverter;
use Swag\PayPal\Pos\Api\Service\MetadataGenerator;
use Swag\PayPal\Pos\Api\Service\ProductConverter;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class ProductConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const TRANSLATION_MARK = '_t';

    public function testConvertMinimal(): void
    {
        $productEntity = $this->createProductEntity();

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency(),
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
    }

    public function testConvertMaximal(): void
    {
        $tax = $this->getTax();

        $productEntity = $this->createProductEntity();
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setCategories(new CategoryCollection([$this->getCategory()]));
        $productEntity->setTax($tax);

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency(),
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));
        $product->setName(self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $product->setDescription(self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $product->setCategory(new Category());
        $product->setVatPercentage($tax->getTaxRate());

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
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
            $this->getCurrency(),
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));
        $product->setVariantOptionDefinitions(new VariantOptionDefinitions());

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
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
            $this->getCurrency(),
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntityParent->getId()));
        $product->setVariantOptionDefinitions(new VariantOptionDefinitions());
        $product->addVariant(new Variant());

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
    }

    public function testConvertWithNoCurrency(): void
    {
        $productEntity = $this->createProductEntity();

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            null,
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
    }

    public function testConvertOversizedDescription(): void
    {
        $productEntity = $this->createProductEntity();
        $productEntity->addTranslated('description', \str_repeat(self::PRODUCT_DESCRIPTION, 100));
        static::assertGreaterThan(1024, \mb_strlen($productEntity->getTranslation('description') ?? ''));

        $converted = $this->createProductConverter()->convertShopwareProducts(
            new ProductCollection([$productEntity]),
            $this->getCurrency(),
            $this->createMock(ProductContext::class)
        );
        $convertedGrouping = $converted->first();

        $product = $this->createProduct();
        $product->setDescription(\sprintf('%s...', \mb_substr(\str_repeat(self::PRODUCT_DESCRIPTION, 100), 0, 1021)));
        $product->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));

        static::assertNotNull($convertedGrouping);
        static::assertEquals($product, $convertedGrouping->getProduct());
    }

    private function createProductEntity(): SalesChannelProductEntity
    {
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setName(self::PRODUCT_NAME);
        $productEntity->setDescription(self::PRODUCT_DESCRIPTION);
        $productEntity->setProductNumber(self::PRODUCT_NUMBER);
        $shopwarePrice = new CalculatedPrice(self::PRODUCT_PRICE, self::PRODUCT_PRICE, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productEntity->setCalculatedPrice($shopwarePrice);

        return $productEntity;
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setName(self::PRODUCT_NAME);
        $product->setDescription(self::PRODUCT_DESCRIPTION);
        $product->addVariant(new Variant());
        $product->assign([
            'metadata' => [
                'inPos' => true,
                'source' => [
                    'external' => true,
                    'name' => SwagPayPal::POS_PARTNER_IDENTIFIER,
                ],
            ],
        ]);

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
            $optionGroupConverter,
            new PresentationConverter(),
            new MetadataGenerator()
        );
    }

    private function createUuidConverter(): UuidConverter
    {
        return new UuidConverter();
    }

    private function getCurrency(): ?CurrencyEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::CURRENCY]);

        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        /** @var CurrencyEntity|null $currency */
        $currency = $currencyRepository->search($criteria, Context::createDefaultContext())->first();

        return $currency;
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();

        /** @var EntityRepository $taxRepository */
        $taxRepository = $this->getContainer()->get('tax.repository');

        /** @var TaxEntity|null $tax */
        $tax = $taxRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($tax);

        return $tax;
    }

    private function getCategory(): CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translation');

        /** @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        /** @var CategoryEntity|null $category */
        $category = $categoryRepository->search($criteria, Context::createDefaultContext())->first();
        static::assertNotNull($category);

        return $category;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Converter;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price as ShopwarePrice;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\Variant\CostPrice;
use Swag\PayPal\Pos\Api\Product\Variant\Option;
use Swag\PayPal\Pos\Api\Product\Variant\Price;
use Swag\PayPal\Pos\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PriceConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\Converter\VariantConverter;
use Swag\PayPal\Pos\Sync\Context\ProductContext;

/**
 * @internal
 */
#[Package('checkout')]
class VariantConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_EAN = '1234567890';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_PRICE_CONVERTED = 1111;
    private const PRODUCT_PURCHASE_PRICE = 10.11;
    private const PRODUCT_PURCHASE_PRICE_CONVERTED = 1011;
    private const TRANSLATION_MARK = '_t';

    private const OPTION1_NAME = 'Option Name 1';
    private const OPTION1_VALUE = 'Option Value 1';
    private const OPTION2_NAME = 'Option Name 2';
    private const OPTION2_VALUE = 'Option Value 2';

    public function testConvertMinimal(): void
    {
        $productEntity = $this->createProductEntity();

        $converted = $this->createVariantConverter()->convert(
            $productEntity,
            null,
            $this->createMock(ProductContext::class)
        );

        $variant = $this->createVariant();

        $uuid = $this->createUuidConverter()->incrementUuid($productEntity->getId());
        $variant->setUuid($this->createUuidConverter()->convertUuidToV1($uuid));

        static::assertEquals($variant, $converted);
    }

    public function testConvertOversizedDescription(): void
    {
        $productEntity = $this->createProductEntity();
        $productEntity->addTranslated('description', \str_repeat(self::PRODUCT_DESCRIPTION, 100));
        static::assertGreaterThan(1024, \mb_strlen($productEntity->getTranslation('description') ?? ''));

        $converted = $this->createVariantConverter()->convert(
            $productEntity,
            null,
            $this->createMock(ProductContext::class)
        );

        $variant = $this->createVariant();
        $variant->setDescription(\sprintf('%s...', \mb_substr(\str_repeat(self::PRODUCT_DESCRIPTION, 100), 0, 1021)));

        $uuid = $this->createUuidConverter()->incrementUuid($productEntity->getId());
        $variant->setUuid($this->createUuidConverter()->convertUuidToV1($uuid));

        static::assertEquals($variant, $converted);
    }

    public function testConvertMaximal(): void
    {
        $currency = $this->getCurrency();

        $productEntity = $this->createProductEntity();
        $productEntity->setParentId(Uuid::randomHex());
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setEan(self::PRODUCT_EAN);
        $productEntity->setOptions($this->createOptions());
        $productEntity->setPurchasePrices(new PriceCollection([
            $currency->getId() => new ShopwarePrice(
                $currency->getId(),
                self::PRODUCT_PURCHASE_PRICE,
                self::PRODUCT_PURCHASE_PRICE,
                false
            ),
        ]));

        $converted = $this->createVariantConverter()->convert(
            $productEntity,
            $currency,
            $this->createMock(ProductContext::class)
        );

        $variant = $this->createVariant();
        $variant->setUuid($this->createUuidConverter()->convertUuidToV1($productEntity->getId()));
        $variant->setName(self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $variant->setDescription(self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $variant->setBarcode(self::PRODUCT_EAN);
        $option1 = new Option();
        $option1->setName(self::OPTION1_NAME);
        $option1->setValue(self::OPTION1_VALUE);
        $option2 = new Option();
        $option2->setName(self::OPTION2_NAME);
        $option2->setValue(self::OPTION2_VALUE);
        $variant->setOptions([$option1, $option2]);

        $currency = $this->getCurrency();
        $price = new Price();
        $price->setAmount(self::PRODUCT_PRICE_CONVERTED);
        $price->setCurrencyId($currency->getIsoCode());
        $variant->setPrice($price);
        $costPrice = new CostPrice();
        $costPrice->setAmount(self::PRODUCT_PURCHASE_PRICE_CONVERTED);
        $costPrice->setCurrencyId($currency->getIsoCode());
        $variant->setCostPrice($costPrice);

        static::assertEquals($variant, $converted);
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

    private function createVariant(): Variant
    {
        $variant = new Variant();
        $variant->setName(self::PRODUCT_NAME);
        $variant->setDescription(self::PRODUCT_DESCRIPTION);
        $variant->setSku(self::PRODUCT_NUMBER);

        return $variant;
    }

    private function createVariantConverter(): VariantConverter
    {
        return new VariantConverter($this->createUuidConverter(), new PriceConverter(), new PresentationConverter(), new NullLogger());
    }

    private function createUuidConverter(): UuidConverter
    {
        return new UuidConverter();
    }

    private function getCurrency(): CurrencyEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::CURRENCY]);

        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        /** @var CurrencyEntity|null $currency */
        $currency = $currencyRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($currency);

        return $currency;
    }

    private function createOptions(): PropertyGroupOptionCollection
    {
        $collection = new PropertyGroupOptionCollection();

        $option = new PropertyGroupOptionEntity();
        $option->setId(Uuid::randomHex());
        $option->setName(self::OPTION1_VALUE);
        $group = new PropertyGroupEntity();
        $group->setId(Uuid::randomHex());
        $group->setName(self::OPTION1_NAME);
        $option->setGroup($group);
        $collection->add($option);

        $option = new PropertyGroupOptionEntity();
        $option->setId(Uuid::randomHex());
        $option->setName(self::OPTION1_VALUE);
        $option->addTranslated('name', self::OPTION2_VALUE);
        $group = new PropertyGroupEntity();
        $group->setId(Uuid::randomHex());
        $group->setName(self::OPTION1_NAME);
        $group->addTranslated('name', self::OPTION2_NAME);
        $option->setGroup($group);
        $collection->add($option);

        return $collection;
    }
}

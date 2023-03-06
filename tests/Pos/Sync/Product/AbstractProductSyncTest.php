<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Product;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;

/**
 * @internal
 */
abstract class AbstractProductSyncTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_EAN = '1234567890';
    private const TRANSLATION_MARK = '_t';

    protected function getProduct(): SalesChannelProductEntity
    {
        $tax = $this->getTax();
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setName(self::PRODUCT_NAME);
        $productEntity->setDescription(self::PRODUCT_DESCRIPTION);
        $productEntity->setProductNumber(self::PRODUCT_NUMBER);
        $productEntity->setEan(self::PRODUCT_EAN);
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setCategories(new CategoryCollection([$this->getCategory()]));
        $productEntity->setTax($tax);
        $shopwarePrice = new CalculatedPrice(self::PRODUCT_PRICE, self::PRODUCT_PRICE, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productEntity->setCalculatedPrice($shopwarePrice);

        return $productEntity;
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();

        /** @var EntityRepository $taxRepository */
        $taxRepository = $this->getContainer()->get('tax.repository');
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

        $category = $categoryRepository->search($criteria, Context::createDefaultContext())->first();
        static::assertNotNull($category);

        return $category;
    }
}

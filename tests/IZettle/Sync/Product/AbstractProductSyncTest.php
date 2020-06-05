<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

abstract class AbstractProductSyncTest extends TestCase
{
    use KernelTestBehaviour;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_EAN = '1234567890';
    private const TRANSLATION_MARK = '_t';

    protected function createSalesChannel(Context $context): SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::SALES_CHANNEL]);
        $criteria->addAssociation('currency');

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($criteria, $context)->first();

        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        $iZettleSalesChannel->setProductStreamId('someProductStreamId');
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setSalesChannelDomainId('someSalesChannelDomainId');
        $iZettleSalesChannel->setSalesChannelId(Defaults::SALES_CHANNEL);

        $salesChannel->addExtension('paypalIZettleSalesChannel', $iZettleSalesChannel);

        return $salesChannel;
    }

    protected function getProduct(): ProductEntity
    {
        $tax = $this->getTax();
        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setName(self::PRODUCT_NAME);
        $productEntity->setDescription(self::PRODUCT_DESCRIPTION);
        $productEntity->setProductNumber(self::PRODUCT_NUMBER);
        $productEntity->setEan(self::PRODUCT_EAN);
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setCategories(new CategoryCollection([$this->getCategory()]));
        $productEntity->setTax($tax);
        $price = new Price(Defaults::CURRENCY, self::PRODUCT_PRICE, self::PRODUCT_PRICE * 1.19, false);
        $productEntity->setPrice(new PriceCollection([$price]));

        return $productEntity;
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();

        /** @var EntityRepositoryInterface $taxRepository */
        $taxRepository = $this->getContainer()->get('tax.repository');
        $tax = $taxRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($tax);

        return $tax;
    }

    private function getCategory(): ?CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translation');

        /** @var EntityRepositoryInterface $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        return $categoryRepository->search($criteria, Context::createDefaultContext())->first();
    }
}

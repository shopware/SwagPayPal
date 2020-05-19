<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\IZettle\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\PriceConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\VariantConverter;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContextFactory;
use Swag\PayPal\IZettle\Sync\Product\DeletedUpdater;
use Swag\PayPal\IZettle\Sync\Product\NewUpdater;
use Swag\PayPal\IZettle\Sync\Product\OutdatedUpdater;
use Swag\PayPal\IZettle\Sync\Product\UnsyncedChecker;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\Mock\IZettle\ProductContextMock;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelProductRepoMock;

class ProductSyncerTest extends TestCase
{
    use ProductTrait;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_EAN = '1234567890';
    private const TRANSLATION_MARK = '_t';

    /**
     * @var MockObject
     */
    private $productContextFactory;

    /**
     * @var ProductContextMock
     */
    private $productContext;

    /**
     * @var ProductSyncer
     */
    private $pruductSyncer;

    /**
     * @var MockObject
     */
    private $productResource;

    /**
     * @var SalesChannelProductRepoMock
     */
    private $productRepository;

    /**
     * @var SalesChannelEntity
     */
    private $salesChannel;

    /**
     * @var MockObject
     */
    private $newUpdater;

    /**
     * @var MockObject
     */
    private $outdatedUpdater;

    /**
     * @var MockObject
     */
    private $deletedUpdater;

    /**
     * @var MockObject
     */
    private $unsyncedChecker;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->createSalesChannel($context);

        $this->productContext = new ProductContextMock($this->salesChannel, $context);
        $this->productContextFactory = $this->createMock(ProductContextFactory::class);
        $this->productContextFactory->method('getContext')->willReturn($this->productContext);

        $productStreamBuilder = $this->createStub(ProductStreamBuilderInterface::class);
        $productStreamBuilder->method('buildFilters')->willReturn(
            [new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('id', null),
            ])]
        );

        $this->productResource = $this->createPartialMock(
            ProductResource::class,
            ['createProduct', 'updateProduct', 'deleteProduct']
        );

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setSalesChannelId($this->salesChannel->getId());
        $domainRepository = $this->createStub(EntityRepositoryInterface::class);
        $domainRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new SalesChannelDomainCollection([$domain]),
                null,
                new Criteria(),
                $context
            )
        );

        $this->productRepository = new SalesChannelProductRepoMock();

        $productSelection = new ProductSelection(
            $this->productRepository,
            $productStreamBuilder,
            $domainRepository,
            $this->createMock(SalesChannelContextFactory::class)
        );

        $this->newUpdater = $this->createMock(NewUpdater::class);
        $this->outdatedUpdater = $this->createMock(OutdatedUpdater::class);
        $this->deletedUpdater = $this->createMock(DeletedUpdater::class);
        $this->unsyncedChecker = $this->createMock(UnsyncedChecker::class);

        $this->pruductSyncer = new ProductSyncer(
            $productSelection,
            new ProductConverter(
                new UuidConverter(),
                new CategoryConverter(new UuidConverter()),
                new VariantConverter(new UuidConverter(), new PriceConverter()),
                new OptionGroupConverter()
            ),
            $this->productContextFactory,
            $this->newUpdater,
            $this->outdatedUpdater,
            $this->deletedUpdater,
            $this->unsyncedChecker
        );
    }

    public function testProductSync(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);

        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::never())->method('createProduct');
        $this->productContextFactory->expects(static::exactly(3))->method('commit');
        $this->newUpdater->expects(static::once())->method('update');
        $this->outdatedUpdater->expects(static::once())->method('update');
        $this->deletedUpdater->expects(static::once())->method('update');
        $this->unsyncedChecker->expects(static::once())->method('checkForUnsynced');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }

    private function getProduct(): ProductEntity
    {
        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setName(self::PRODUCT_NAME);
        $productEntity->setDescription(self::PRODUCT_DESCRIPTION);
        $productEntity->setProductNumber(self::PRODUCT_NUMBER);
        $productEntity->setEan(self::PRODUCT_EAN);
        $productEntity->addTranslated('name', self::PRODUCT_NAME . self::TRANSLATION_MARK);
        $productEntity->addTranslated('description', self::PRODUCT_DESCRIPTION . self::TRANSLATION_MARK);
        $productEntity->setCategories(new CategoryCollection([$this->getCategory()]));
        $productEntity->setTax($this->getTax());
        $price = new Price(Defaults::CURRENCY, self::PRODUCT_PRICE, self::PRODUCT_PRICE * 1.19, false);
        $productEntity->setPrice(new PriceCollection([$price]));

        return $productEntity;
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

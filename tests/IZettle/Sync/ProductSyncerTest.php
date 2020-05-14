<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync;

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
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\PriceConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\VariantConverter;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\IZettle\ChecksumResourceMock;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelProductRepoMock;

class ProductSyncerTest extends TestCase
{
    use KernelTestBehaviour;

    private const PRODUCT_NAME = 'Product Name';
    private const PRODUCT_DESCRIPTION = 'Product Description';
    private const PRODUCT_NUMBER = 'Product Description';
    private const PRODUCT_PRICE = 11.11;
    private const PRODUCT_EAN = '1234567890';
    private const TRANSLATION_MARK = '_t';

    /**
     * @var ChecksumResourceMock
     */
    private $checksumResource;

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

    public function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->getSalesChannel($context);

        $this->checksumResource = new ChecksumResourceMock();

        $productStreamBuilder = $this->createStub(ProductStreamBuilderInterface::class);
        $productStreamBuilder->method('buildFilters')->willReturn(
            [new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('id', null),
            ])]
        );

        $this->productResource = $this->createPartialMock(
            ProductResource::class,
            ['createProduct', 'updateProduct']
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

        $this->pruductSyncer = new ProductSyncer(
            $productSelection,
            $this->productResource,
            new ProductConverter(
                new UuidConverter(),
                new CategoryConverter(new UuidConverter()),
                new VariantConverter(new UuidConverter(), new PriceConverter()),
                new OptionGroupConverter()
            ),
            $this->checksumResource
        );
    }

    public function testProductSyncCurrentProduct(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);
        $this->checksumResource->addCurrentId($product->getId());

        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::never())->method('createProduct');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertEquals(2, $this->checksumResource->getStatus());
        static::assertCount(0, $this->checksumResource->getUpdatedProducts());
        static::assertCount(0, $this->checksumResource->getRemovedProducts());
    }

    public function testProductSyncOutdatedProduct(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);
        $this->checksumResource->addOutdatedId($product->getId());

        $this->productResource->expects(static::once())->method('updateProduct');
        $this->productResource->expects(static::never())->method('createProduct');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertEquals(2, $this->checksumResource->getStatus());
        static::assertCount(1, $this->checksumResource->getUpdatedProducts());
        static::assertCount(0, $this->checksumResource->getRemovedProducts());
    }

    public function testProductSyncNewProduct(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);

        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::once())->method('createProduct');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertEquals(2, $this->checksumResource->getStatus());
        static::assertCount(1, $this->checksumResource->getUpdatedProducts());
        static::assertCount(0, $this->checksumResource->getRemovedProducts());
    }

    public function testProductSyncExistingProduct(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);

        $error = new IZettleApiError();
        $error->assign([
            'errorType' => IZettleApiError::ERROR_TYPE_ITEM_ALREADY_EXISTS,
            'developerMessage' => IZettleApiError::ERROR_TYPE_ITEM_ALREADY_EXISTS,
            'violations' => [], ]);
        $this->productResource->method('createProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->productResource->expects(static::once())->method('updateProduct');
        $this->productResource->expects(static::once())->method('createProduct');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertEquals(2, $this->checksumResource->getStatus());
        static::assertCount(1, $this->checksumResource->getUpdatedProducts());
        static::assertCount(0, $this->checksumResource->getRemovedProducts());
    }

    public function testProductSyncRemovedProduct(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);
        $this->checksumResource->addOutdatedId($product->getId());

        $error = new IZettleApiError();
        $error->assign([
            'errorType' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'developerMessage' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $this->productResource->method('updateProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->productResource->expects(static::once())->method('updateProduct');
        $this->productResource->expects(static::never())->method('createProduct');

        $this->pruductSyncer->syncProducts($this->salesChannel, $context);

        static::assertEquals(2, $this->checksumResource->getStatus());
        static::assertCount(0, $this->checksumResource->getUpdatedProducts());
        static::assertCount(1, $this->checksumResource->getRemovedProducts());
    }

    public function testProductSyncErrorCreate(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $this->productResource->method('createProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->expectException(IZettleApiException::class);
        $this->pruductSyncer->syncProducts($this->salesChannel, $context);
    }

    public function testProductSyncErrorUpdate(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);
        $this->checksumResource->addOutdatedId($product->getId());

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $this->productResource->method('updateProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->expectException(IZettleApiException::class);
        $this->pruductSyncer->syncProducts($this->salesChannel, $context);
    }

    private function getSalesChannel(Context $context): SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([Defaults::SALES_CHANNEL]);
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->getContainer()->get('sales_channel.repository')->search($criteria, $context)->first();

        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        if (random_int(0, 1)) {
            $iZettleSalesChannel->setProductStreamId('someProductStreamId');
        }
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setSalesChannelDomainId('someSalesChannelDomainId');

        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, $iZettleSalesChannel);

        return $salesChannel;
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

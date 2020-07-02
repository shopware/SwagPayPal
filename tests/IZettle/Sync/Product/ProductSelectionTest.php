<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunLogCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunLogEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContextFactory;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\IZettle\ProductContextMock;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelProductRepoMock;

class ProductSelectionTest extends AbstractProductSyncTest
{
    /**
     * @var MockObject
     */
    private $productContextFactory;

    /**
     * @var ProductContextMock
     */
    private $productContext;

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
     * @var ProductSelection
     */
    private $productSelection;

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

        $this->productRepository = new SalesChannelProductRepoMock();

        $this->productSelection = new ProductSelection(
            $this->productRepository,
            $productStreamBuilder,
            $this->createMock(SalesChannelContextFactory::class)
        );
    }

    public function dataProviderProductSelection(): array
    {
        return [
            [false, false],
            [false, true],
            [true, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider dataProviderProductSelection
     */
    public function testProductSelection(bool $withProductStream, bool $withAssociations): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $this->productRepository->addMockEntity($product);

        $iZettleSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);
        static::assertNotNull($iZettleSalesChannel);
        static::assertInstanceOf(IZettleSalesChannelEntity::class, $iZettleSalesChannel);
        if (!$withProductStream) {
            $iZettleSalesChannel->setProductStreamId(null);
        }

        $products = $this->productSelection->getProductCollection($this->salesChannel, $context, $withAssociations);

        static::assertCount(1, $products);
    }

    public function testProductLog(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $productLogCollection = new IZettleSalesChannelRunLogCollection();
        $log = new IZettleSalesChannelRunLogEntity();
        $log->setId(Uuid::randomHex());
        $productLogCollection->add($log);
        $product->addExtension(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION, $productLogCollection);
        $this->productRepository->addMockEntity($product);

        $iZettleSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);
        static::assertNotNull($iZettleSalesChannel);
        static::assertInstanceOf(IZettleSalesChannelEntity::class, $iZettleSalesChannel);
        $products = $this->productSelection->getProductLogCollection($this->salesChannel, 10, 1, $context);

        $firstProduct = $products->first();
        static::assertNotNull($firstProduct);
        static::assertSame($product, $firstProduct);
        static::assertSame($productLogCollection, $firstProduct->getExtension(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION));
    }
}

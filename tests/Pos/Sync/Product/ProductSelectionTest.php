<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Product;

use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogEntity;
use Swag\PayPal\Pos\Sync\Context\ProductContextFactory;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\Mock\ProductContextMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelProductRepoMock;

/**
 * @internal
 */
#[Package('checkout')]
class ProductSelectionTest extends AbstractProductSyncTest
{
    private SalesChannelProductRepoMock $productRepository;

    private SalesChannelEntity $salesChannel;

    private ProductSelection $productSelection;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();

        $this->salesChannel = $this->getSalesChannel($context);

        $productContext = new ProductContextMock($this->salesChannel, $context);
        $productContextFactory = $this->createMock(ProductContextFactory::class);
        $productContextFactory->method('getContext')->willReturn($productContext);

        $productStreamBuilder = $this->createStub(ProductStreamBuilderInterface::class);
        $productStreamBuilder->method('buildFilters')->willReturn(
            [new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('id', null),
            ])]
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

    public function testProductLog(): void
    {
        $context = Context::createDefaultContext();

        $product = $this->getProduct();
        $productLogCollection = new PosSalesChannelRunLogCollection();
        $log = new PosSalesChannelRunLogEntity();
        $log->setId(Uuid::randomHex());
        $productLogCollection->add($log);
        $product->addExtension(SwagPayPal::PRODUCT_LOG_POS_EXTENSION, $productLogCollection);
        $this->productRepository->addMockEntity($product);

        $posSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        static::assertNotNull($posSalesChannel);
        static::assertInstanceOf(PosSalesChannelEntity::class, $posSalesChannel);
        $products = $this->productSelection->getProductLogCollection($this->salesChannel, 10, 1, $context);

        $firstProduct = $products->first();
        static::assertNotNull($firstProduct);
        static::assertSame($product, $firstProduct);
        static::assertSame($productLogCollection, $firstProduct->getExtension(SwagPayPal::PRODUCT_LOG_POS_EXTENSION));
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Product;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\Pos\Sync\Product\OutdatedUpdater;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGroupingCollection;
use Swag\PayPal\Test\Pos\Mock\ProductContextMock;

class OutdatedUpdaterTest extends AbstractProductSyncTest
{
    /**
     * @var ProductContextMock
     */
    private $productContext;

    /**
     * @var ProductGroupingCollection
     */
    private $productGroupingCollection;

    /**
     * @var MockObject|ProductResource
     */
    private $productResource;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();
        $salesChannel = $this->getSalesChannel($context);

        $this->productContext = new ProductContextMock($salesChannel, $context);

        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setVersionId(Uuid::randomHex());

        $this->productGroupingCollection = new ProductGroupingCollection();
        $this->productGroupingCollection->addProducts(new ProductCollection([$productEntity]));
        $grouping = $this->productGroupingCollection->first();
        if ($grouping !== null) {
            $grouping->setProduct(new Product());
        }

        $this->productResource = $this->createMock(ProductResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testOutdatedProduct(): void
    {
        $updater = new OutdatedUpdater($this->productResource, $this->logger);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $this->productResource->expects(static::never())->method('createProduct');
        $this->productResource->expects(static::once())->method('updateProduct');
        $this->productResource->expects(static::never())->method('deleteProducts');
        $this->logger->expects(static::once())->method('info');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(1, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }

    public function testOutdatedProductButNotExistsAtPos(): void
    {
        $updater = new OutdatedUpdater($this->productResource, $this->logger);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $error = new PosApiError();
        $error->assign([
            'errorType' => PosApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'developerMessage' => PosApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $this->productResource->method('updateProduct')->willThrowException(
            new PosApiException($error)
        );

        $this->productResource->expects(static::never())->method('createProduct');
        $this->productResource->expects(static::once())->method('updateProduct');
        $this->productResource->expects(static::never())->method('deleteProducts');
        $this->logger->expects(static::once())->method('notice');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(1, $this->productContext->getProductRemovals());
    }

    public function testOutdatedProductUpdateError(): void
    {
        $updater = new OutdatedUpdater($this->productResource, $this->logger);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $error = new PosApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->productResource->method('updateProduct')->willThrowException(
            new PosApiException($error)
        );

        $this->logger->expects(static::once())->method('error');
        $updater->update($this->productGroupingCollection, $this->productContext);
    }

    public function dataProviderUnwantedStatus(): array
    {
        return [
            [ProductContext::PRODUCT_NEW],
            [ProductContext::PRODUCT_CURRENT],
        ];
    }

    /**
     * @dataProvider dataProviderUnwantedStatus
     */
    public function testAnyOtherProduct(int $unwantedStatus): void
    {
        $updater = new OutdatedUpdater($this->productResource, $this->logger);
        $this->productContext->setUpdateStatus($unwantedStatus);

        $this->productResource->expects(static::never())->method('createProduct');
        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::never())->method('deleteProducts');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;
use Swag\PayPal\IZettle\Sync\Product\OutdatedUpdater;
use Swag\PayPal\Test\Mock\IZettle\ProductContextMock;

class OutdatedUpdaterTest extends TestCase
{
    use ProductTrait;

    /**
     * @var ProductContextMock
     */
    private $productContext;

    /**
     * @var ProductGroupingCollection
     */
    private $productGroupingCollection;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();
        $salesChannel = $this->createSalesChannel($context);

        $this->productContext = new ProductContextMock($salesChannel, $context);

        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setVersionId(Uuid::randomHex());

        $this->productGroupingCollection = new ProductGroupingCollection();
        $this->productGroupingCollection->addProducts(new ProductCollection([$productEntity]));
        $grouping = $this->productGroupingCollection->first();
        if ($grouping !== null) {
            $grouping->setProduct(new Product());
        }
    }

    public function testOutdatedProduct(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new OutdatedUpdater($productResource);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::once())->method('updateProduct');
        $productResource->expects(static::never())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(1, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }

    public function testOutdatedProductButNotExistsAtIZettle(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new OutdatedUpdater($productResource);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $error = new IZettleApiError();
        $error->assign([
            'errorType' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'developerMessage' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $productResource->method('updateProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::once())->method('updateProduct');
        $productResource->expects(static::never())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(1, $this->productContext->getProductRemovals());
    }

    public function testOutdatedProductUpdateError(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new OutdatedUpdater($productResource);
        $this->productContext->setUpdateStatus(ProductContext::PRODUCT_OUTDATED);

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $productResource->method('updateProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->expectException(IZettleApiException::class);
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
        $productResource = $this->createMock(ProductResource::class);
        $updater = new OutdatedUpdater($productResource);
        $this->productContext->setUpdateStatus($unwantedStatus);

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::never())->method('updateProduct');
        $productResource->expects(static::never())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }
}

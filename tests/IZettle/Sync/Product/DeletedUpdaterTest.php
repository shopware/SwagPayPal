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
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Product\DeletedUpdater;
use Swag\PayPal\Test\Mock\IZettle\ProductContextMock;

class DeletedUpdaterTest extends TestCase
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

    /**
     * @var IZettleSalesChannelProductEntity
     */
    private $iZettleProductEntity;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();
        $salesChannel = $this->createSalesChannel($context);

        $this->iZettleProductEntity = new IZettleSalesChannelProductEntity();
        $this->iZettleProductEntity->setSalesChannelId($salesChannel->getId());
        $this->iZettleProductEntity->setProductId(Uuid::randomHex());
        $this->iZettleProductEntity->setProductVersionId(Uuid::randomHex());
        $this->iZettleProductEntity->setUniqueIdentifier(Uuid::randomHex());
        $this->iZettleProductEntity->setChecksum('aChecksum');

        $this->productContext = new ProductContextMock($salesChannel, $context, $this->iZettleProductEntity);

        $this->productGroupingCollection = new ProductGroupingCollection([]);
    }

    public function testDeletedProduct(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new DeletedUpdater($productResource, new UuidConverter());

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::never())->method('updateProduct');
        $productResource->expects(static::once())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(1, $this->productContext->getProductRemovals());
    }

    public function testNoDeletedProduct(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new DeletedUpdater($productResource, new UuidConverter());

        $productEntity = new ProductEntity();
        $productEntity->setId($this->iZettleProductEntity->getProductId());
        $productEntity->setVersionId($this->iZettleProductEntity->getProductVersionId());
        $this->productGroupingCollection->addProducts(new ProductCollection([$productEntity]));

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::never())->method('updateProduct');
        $productResource->expects(static::never())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }

    public function testDeletedProductButNotExistsAtIZettle(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new DeletedUpdater($productResource, new UuidConverter());

        $error = new IZettleApiError();
        $error->assign([
            'errorType' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'developerMessage' => IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND,
            'violations' => [], ]);
        $productResource->method('deleteProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $productResource->expects(static::never())->method('createProduct');
        $productResource->expects(static::never())->method('updateProduct');
        $productResource->expects(static::once())->method('deleteProduct');

        $updater->update($this->productGroupingCollection, $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(1, $this->productContext->getProductRemovals());
    }

    public function testDeletedProductDeletionError(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $updater = new DeletedUpdater($productResource, new UuidConverter());

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $productResource->method('deleteProduct')->willThrowException(
            new IZettleApiException($error)
        );

        $this->expectException(IZettleApiException::class);
        $updater->update($this->productGroupingCollection, $this->productContext);
    }
}

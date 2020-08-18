<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Product\DeletedUpdater;
use Swag\PayPal\Test\IZettle\Mock\ProductContextMock;

class DeletedUpdaterTest extends AbstractProductSyncTest
{
    /**
     * @var ProductContextMock
     */
    private $productContext;

    /**
     * @var IZettleSalesChannelProductEntity
     */
    private $iZettleProductEntity;

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

        $this->iZettleProductEntity = new IZettleSalesChannelProductEntity();
        $this->iZettleProductEntity->setSalesChannelId($salesChannel->getId());
        $this->iZettleProductEntity->setProductId(Uuid::randomHex());
        $this->iZettleProductEntity->setProductVersionId(Uuid::randomHex());
        $this->iZettleProductEntity->setUniqueIdentifier(Uuid::randomHex());
        $this->iZettleProductEntity->setChecksum('aChecksum');

        $this->productContext = new ProductContextMock($salesChannel, $context, $this->iZettleProductEntity);

        $this->productResource = $this->createMock(ProductResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testDeletedProduct(): void
    {
        $updater = new DeletedUpdater(
            $this->productResource,
            $this->logger,
            new UuidConverter()
        );

        $this->productResource->expects(static::never())->method('createProduct');
        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::once())->method('deleteProducts');
        $this->logger->expects(static::once())->method('info');

        $updater->update([], $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(1, $this->productContext->getProductRemovals());
    }

    public function testNoDeletedProduct(): void
    {
        $updater = new DeletedUpdater(
            $this->productResource,
            $this->logger,
            new UuidConverter()
        );

        $this->productResource->expects(static::never())->method('createProduct');
        $this->productResource->expects(static::never())->method('updateProduct');
        $this->productResource->expects(static::never())->method('deleteProducts');
        $this->logger->expects(static::never())->method('info');

        $updater->update([$this->iZettleProductEntity->getProductId()], $this->productContext);

        static::assertCount(0, $this->productContext->getProductChanges());
        static::assertCount(0, $this->productContext->getProductRemovals());
    }

    public function testDeletedProductDeletionError(): void
    {
        $updater = new DeletedUpdater(
            $this->productResource,
            $this->logger,
            new UuidConverter()
        );

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->productResource->method('deleteProducts')->willThrowException(
            new IZettleApiException($error)
        );

        $this->logger->expects(static::once())->method('error');
        $updater->update([], $this->productContext);
    }
}

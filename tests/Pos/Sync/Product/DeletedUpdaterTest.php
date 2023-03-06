<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Product;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Product\DeletedUpdater;
use Swag\PayPal\Test\Pos\Mock\ProductContextMock;

/**
 * @internal
 */
class DeletedUpdaterTest extends AbstractProductSyncTest
{
    private ProductContextMock $productContext;

    private PosSalesChannelProductEntity $posProductEntity;

    /**
     * @var MockObject&ProductResource
     */
    private ProductResource $productResource;

    /**
     * @var MockObject&LoggerInterface
     */
    private LoggerInterface $logger;

    public function setUp(): void
    {
        $context = Context::createDefaultContext();
        $salesChannel = $this->getSalesChannel($context);

        $this->posProductEntity = new PosSalesChannelProductEntity();
        $this->posProductEntity->setSalesChannelId($salesChannel->getId());
        $this->posProductEntity->setProductId(Uuid::randomHex());
        $this->posProductEntity->setProductVersionId(Uuid::randomHex());
        $this->posProductEntity->setUniqueIdentifier(Uuid::randomHex());
        $this->posProductEntity->setChecksum('aChecksum');

        $this->productContext = new ProductContextMock($salesChannel, $context, $this->posProductEntity);

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

        $updater->update([$this->posProductEntity->getProductId()], $this->productContext);

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

        $error = new PosApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->productResource->method('deleteProducts')->willThrowException(
            new PosApiException($error)
        );

        $this->logger->expects(static::once())->method('error');
        $updater->update([], $this->productContext);
    }
}

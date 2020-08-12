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
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Product\UnsyncedChecker;
use Swag\PayPal\Test\IZettle\Mock\ProductContextMock;

class UnsyncedCheckerTest extends AbstractProductSyncTest
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
     * @var Product
     */
    private $iZettleProduct;

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

        $this->iZettleProduct = new Product();
        $this->iZettleProduct->setUuid((new UuidConverter())->convertUuidToV1($this->iZettleProductEntity->getProductId()));

        $this->productContext = new ProductContextMock($salesChannel, $context, null);
        $this->productContext->getIZettleSalesChannel()->setReplace(true);

        $this->productResource = $this->createMock(ProductResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testUnsyncedProduct(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->iZettleProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::once())->method('deleteProducts');
        $this->logger->expects(static::once())->method('info');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testSyncedProductInSalesChannel(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->iZettleProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([$this->iZettleProductEntity->getProductId()], $this->productContext);
    }

    public function testSyncedProductInLog(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->iZettleProduct]);
        $this->productContext->getIZettleProductCollection()->add($this->iZettleProductEntity);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testUnsyncedProductWithReplaceDisabled(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->iZettleProduct]);
        $this->productContext->getIZettleSalesChannel()->setReplace(false);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testUnsyncedProductDeletionError(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->iZettleProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $error = new IZettleApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->productResource->method('deleteProducts')->willThrowException(
            new IZettleApiException($error)
        );

        $this->logger->expects(static::once())->method('warning');
        $updater->checkForUnsynced([], $this->productContext);
    }
}

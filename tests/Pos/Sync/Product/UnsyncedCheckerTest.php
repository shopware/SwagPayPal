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
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Product\UnsyncedChecker;
use Swag\PayPal\Test\Pos\Mock\ProductContextMock;

class UnsyncedCheckerTest extends AbstractProductSyncTest
{
    /**
     * @var ProductContextMock
     */
    private $productContext;

    /**
     * @var PosSalesChannelProductEntity
     */
    private $posProductEntity;

    /**
     * @var Product
     */
    private $posProduct;

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

        $this->posProductEntity = new PosSalesChannelProductEntity();
        $this->posProductEntity->setSalesChannelId($salesChannel->getId());
        $this->posProductEntity->setProductId(Uuid::randomHex());
        $this->posProductEntity->setProductVersionId(Uuid::randomHex());
        $this->posProductEntity->setUniqueIdentifier(Uuid::randomHex());
        $this->posProductEntity->setChecksum('aChecksum');

        $this->posProduct = new Product();
        $this->posProduct->setUuid((new UuidConverter())->convertUuidToV1($this->posProductEntity->getProductId()));

        $this->productContext = new ProductContextMock($salesChannel, $context, null);
        $this->productContext->getPosSalesChannel()->setReplace(true);

        $this->productResource = $this->createMock(ProductResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testUnsyncedProduct(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->posProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::once())->method('deleteProducts');
        $this->logger->expects(static::once())->method('info');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testSyncedProductInSalesChannel(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->posProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([$this->posProductEntity->getProductId()], $this->productContext);
    }

    public function testSyncedProductInLog(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->posProduct]);
        $this->productContext->getPosProductCollection()->add($this->posProductEntity);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testUnsyncedProductWithReplaceDisabled(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->posProduct]);
        $this->productContext->getPosSalesChannel()->setReplace(false);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $this->productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced([], $this->productContext);
    }

    public function testUnsyncedProductDeletionError(): void
    {
        $this->productResource->method('getProducts')->willReturn([$this->posProduct]);

        $updater = new UnsyncedChecker($this->productResource, $this->logger, new UuidConverter());

        $error = new PosApiError();
        $error->assign([
            'developerMessage' => 'anyError',
            'violations' => [], ]);
        $this->productResource->method('deleteProducts')->willThrowException(
            new PosApiException($error)
        );

        $this->logger->expects(static::once())->method('warning');
        $updater->checkForUnsynced([], $this->productContext);
    }
}

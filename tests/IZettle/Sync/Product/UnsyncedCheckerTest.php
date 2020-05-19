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
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Product\UnsyncedChecker;
use Swag\PayPal\Test\Mock\IZettle\ProductContextMock;

class UnsyncedCheckerTest extends TestCase
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

    /**
     * @var Product
     */
    private $iZettleProduct;

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

        $this->iZettleProduct = new Product();
        $this->iZettleProduct->setUuid((new UuidConverter())->convertUuidToV1($this->iZettleProductEntity->getProductId()));

        $this->productContext = new ProductContextMock($salesChannel, $context, null);
        $this->productContext->getIZettleSalesChannel()->setReplace(true);

        $this->productGroupingCollection = new ProductGroupingCollection([]);
    }

    public function testUnsyncedProduct(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $productResource->method('getProducts')->willReturn([$this->iZettleProduct]);

        $updater = new UnsyncedChecker($productResource, new UuidConverter());

        $productResource->expects(static::never())->method('deleteProduct');
        $productResource->expects(static::once())->method('deleteProducts');
        $updater->checkForUnsynced($this->productGroupingCollection, $this->productContext);
    }

    public function testSyncedProductInSalesChannel(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $productResource->method('getProducts')->willReturn([$this->iZettleProduct]);

        $productEntity = new ProductEntity();
        $productEntity->setId($this->iZettleProductEntity->getProductId());
        $productEntity->setVersionId($this->iZettleProductEntity->getProductVersionId());
        $this->productGroupingCollection->addProducts(new ProductCollection([$productEntity]));

        $updater = new UnsyncedChecker($productResource, new UuidConverter());

        $productResource->expects(static::never())->method('deleteProduct');
        $productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced($this->productGroupingCollection, $this->productContext);
    }

    public function testSyncedProductInLog(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $productResource->method('getProducts')->willReturn([$this->iZettleProduct]);
        $this->productContext->getIZettleProductCollection()->add($this->iZettleProductEntity);

        $updater = new UnsyncedChecker($productResource, new UuidConverter());

        $productResource->expects(static::never())->method('deleteProduct');
        $productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced($this->productGroupingCollection, $this->productContext);
    }

    public function testUnsyncedProductWithReplaceDisabled(): void
    {
        $productResource = $this->createMock(ProductResource::class);
        $productResource->method('getProducts')->willReturn([$this->iZettleProduct]);
        $this->productContext->getIZettleSalesChannel()->setReplace(false);

        $updater = new UnsyncedChecker($productResource, new UuidConverter());

        $productResource->expects(static::never())->method('deleteProduct');
        $productResource->expects(static::never())->method('deleteProducts');
        $updater->checkForUnsynced($this->productGroupingCollection, $this->productContext);
    }
}

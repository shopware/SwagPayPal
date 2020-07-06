<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;
use Swag\PayPal\IZettle\Sync\Context\ProductContextFactory;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleMediaRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleProductRepoMock;

class ProductContextFactoryTest extends AbstractProductSyncTest
{
    private const IMAGE_MEDIA_ID_EXISTING = 'existingMediaId';
    private const IMAGE_MEDIA_ID_NEW = 'newMediaId';
    private const IMAGE_URL = 'https://image.izettle.com/product/BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ.jpeg';
    private const IMAGE_LOOKUP_KEY = 'BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ';

    public function dataProviderCheckForUpdate(): array
    {
        return [
            ['The name', 'The name', ProductContext::PRODUCT_CURRENT],
            ['The old name', 'The new name', ProductContext::PRODUCT_OUTDATED],
            [null, 'No name', ProductContext::PRODUCT_NEW],
        ];
    }

    /**
     * @dataProvider dataProviderCheckForUpdate
     */
    public function testCheckForUpdate(?string $oldName, string $newName, int $status): void
    {
        $context = Context::createDefaultContext();

        $productEntity = $this->createProductEntity();
        $iZettleProductCollection = new IZettleSalesChannelProductCollection();
        if ($oldName !== null) {
            $product = new Product();
            $product->setName($oldName);

            $entity = new IZettleSalesChannelProductEntity();
            $entity->setSalesChannelId(Defaults::SALES_CHANNEL);
            $entity->setProductId($productEntity->getId());
            $versionId = $productEntity->getVersionId();
            if ($versionId !== null) {
                $entity->setProductVersionId($versionId);
            }
            $entity->setUniqueIdentifier(Uuid::randomHex());
            $entity->setChecksum($product->generateChecksum());
            $iZettleProductCollection->add($entity);
        }

        $productContext = new ProductContext($this->createSalesChannel($context), $iZettleProductCollection, new IZettleSalesChannelMediaCollection(), $context);

        $product = new Product();
        $product->setName($newName);
        static::assertEquals($status, $productContext->checkForUpdate($productEntity, $product));
    }

    public function testCheckForDefectiveMedia(): void
    {
        $productContext = $this->createContextForMedia();

        $defectiveMedia = new MediaEntity();
        $defectiveMedia->setId(self::IMAGE_MEDIA_ID_EXISTING);
        static::assertNull($productContext->checkForMediaUrl($defectiveMedia));
        static::assertEmpty($productContext->getMediaRequests());
    }

    public function testCheckForUploadedMedia(): void
    {
        $productContext = $this->createContextForMedia();

        $existingMedia = new MediaEntity();
        $existingMedia->setId(self::IMAGE_MEDIA_ID_EXISTING);
        $existingMedia->setMimeType('image/jpeg');
        $existingMedia->setFileExtension('jpg');
        $existingMedia->setFileName('filename');
        static::assertEquals(self::IMAGE_URL, $productContext->checkForMediaUrl($existingMedia));
        static::assertEmpty($productContext->getMediaRequests());
    }

    public function testCheckForNewMedia(): void
    {
        $productContext = $this->createContextForMedia();

        $newMedia = new MediaEntity();
        $newMedia->setId(self::IMAGE_MEDIA_ID_NEW);
        $newMedia->setMimeType('image/jpeg');
        $newMedia->setFileExtension('jpg');
        $newMedia->setFileName('filename');
        static::assertNull($productContext->checkForMediaUrl($newMedia));
        static::assertContains(
            [
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'mediaId' => self::IMAGE_MEDIA_ID_NEW,
            ],
            $productContext->getMediaRequests()
        );
    }

    public function testCommit(): void
    {
        $context = Context::createDefaultContext();

        $iZettleProductRepoMock = new IZettleProductRepoMock();
        $iZettleMediaRepoMock = new IZettleMediaRepoMock();
        $productContextFactory = new ProductContextFactory($iZettleProductRepoMock, $iZettleMediaRepoMock);

        $productContext = new ProductContext($this->createSalesChannel($context), new IZettleSalesChannelProductCollection(), new IZettleSalesChannelMediaCollection(), $context);

        $productEntity = $this->createProductEntity();
        $convertedProductOriginal = new Product();
        $convertedProductOriginal->setName('test');
        $convertedProductChanged = new Product();
        $originalState = clone $iZettleProductRepoMock->createMockEntity($productEntity, $convertedProductOriginal, Defaults::SALES_CHANNEL);
        $productContext->changeProduct($productEntity, $convertedProductChanged);

        $newMedia = new MediaEntity();
        $newMedia->setId(self::IMAGE_MEDIA_ID_NEW);
        $newMedia->setMimeType('image/jpeg');
        $newMedia->setFileExtension('jpg');
        $newMedia->setFileName('filename');
        $productContext->checkForMediaUrl($newMedia);

        $productContextFactory->commit($productContext);

        static::assertCount(1, $iZettleMediaRepoMock->getCollection());
        static::assertCount(1, $iZettleProductRepoMock->getCollection());
        static::assertNotEquals($originalState, $iZettleProductRepoMock->getCollection()->first());

        $productContext->removeProduct($productEntity);
        $productContextFactory->commit($productContext);
        static::assertEmpty($iZettleProductRepoMock->getCollection());
    }

    public function testIdentical(): void
    {
        $context = Context::createDefaultContext();

        $iZettleProductRepoMock = new IZettleProductRepoMock();
        $iZettleMediaRepoMock = new IZettleMediaRepoMock();
        $productContextFactory = new ProductContextFactory($iZettleProductRepoMock, $iZettleMediaRepoMock);
        $salesChannel = $this->createSalesChannel($context);

        $inventoryContextFirst = $productContextFactory->getContext($salesChannel, $context);
        $inventoryContextSecond = $productContextFactory->getContext($salesChannel, $context);

        static::assertSame($inventoryContextFirst, $inventoryContextSecond);
    }

    private function createProductEntity(): SalesChannelProductEntity
    {
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setVersionId(Uuid::randomHex());

        return $productEntity;
    }

    private function createContextForMedia(): ProductContext
    {
        $context = Context::createDefaultContext();
        $iZettleMedia = new IZettleSalesChannelMediaEntity();
        $iZettleMedia->setUrl(self::IMAGE_URL);
        $iZettleMedia->setLookupKey(self::IMAGE_LOOKUP_KEY);
        $iZettleMedia->setMediaId(self::IMAGE_MEDIA_ID_EXISTING);
        $iZettleMedia->setSalesChannelId(Defaults::SALES_CHANNEL);
        $iZettleMedia->setUniqueIdentifier(Uuid::randomHex());
        $iZettleMediaCollection = new IZettleSalesChannelMediaCollection([$iZettleMedia]);
        $productContext = new ProductContext($this->createSalesChannel($context), new IZettleSalesChannelProductCollection(), $iZettleMediaCollection, $context);

        return $productContext;
    }
}

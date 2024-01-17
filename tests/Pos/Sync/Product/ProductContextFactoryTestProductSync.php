<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\Pos\Sync\Context\ProductContextFactory;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosMediaRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosProductRepoMock;

/**
 * @internal
 */
#[Package('checkout')]
class ProductContextFactoryTestProductSync extends AbstractTestProductSync
{
    private const IMAGE_MEDIA_ID_EXISTING = 'existingMediaId';
    private const IMAGE_MEDIA_ID_NEW = 'newMediaId';
    private const IMAGE_URL = 'https://image.izettle.com/product/BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ.jpeg';
    private const IMAGE_LOOKUP_KEY = 'BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ';

    public static function dataProviderCheckForUpdate(): array
    {
        return [
            ['The name', 'The name', ProductContext::PRODUCT_CURRENT],
            ['The old name', 'The new name', ProductContext::PRODUCT_OUTDATED],
            [null, 'No name', ProductContext::PRODUCT_NEW],
        ];
    }

    #[DataProvider('dataProviderCheckForUpdate')]
    public function testCheckForUpdate(?string $oldName, string $newName, int $status): void
    {
        $context = Context::createDefaultContext();

        $productEntity = $this->createProductEntity();
        $posProductCollection = new PosSalesChannelProductCollection();
        if ($oldName !== null) {
            $product = new Product();
            $product->setName($oldName);

            $entity = new PosSalesChannelProductEntity();
            $entity->setSalesChannelId(TestDefaults::SALES_CHANNEL);
            $entity->setProductId($productEntity->getId());
            $versionId = $productEntity->getVersionId();
            if ($versionId !== null) {
                $entity->setProductVersionId($versionId);
            }
            $entity->setUniqueIdentifier(Uuid::randomHex());
            $entity->setChecksum($product->generateChecksum());
            $posProductCollection->add($entity);
        }

        $productContext = new ProductContext($this->getSalesChannel($context), $posProductCollection, new PosSalesChannelMediaCollection(), $context);

        $product = new Product();
        $product->setName($newName);
        static::assertSame($status, $productContext->checkForUpdate($productEntity, $product));
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
        static::assertSame(self::IMAGE_URL, $productContext->checkForMediaUrl($existingMedia));
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
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'mediaId' => self::IMAGE_MEDIA_ID_NEW,
            ],
            $productContext->getMediaRequests()
        );
    }

    public function testCommit(): void
    {
        $context = Context::createDefaultContext();

        $posProductRepoMock = new PosProductRepoMock();
        $posMediaRepoMock = new PosMediaRepoMock();
        $productContextFactory = new ProductContextFactory($posProductRepoMock, $posMediaRepoMock);

        $productContext = new ProductContext($this->getSalesChannel($context), new PosSalesChannelProductCollection(), new PosSalesChannelMediaCollection(), $context);

        $productEntity = $this->createProductEntity();
        $convertedProductOriginal = new Product();
        $convertedProductOriginal->setName('test');
        $convertedProductChanged = new Product();
        $originalState = clone $posProductRepoMock->createMockEntity($productEntity, $convertedProductOriginal, TestDefaults::SALES_CHANNEL);
        $productContext->changeProduct($productEntity, $convertedProductChanged);

        $newMedia = new MediaEntity();
        $newMedia->setId(self::IMAGE_MEDIA_ID_NEW);
        $newMedia->setMimeType('image/jpeg');
        $newMedia->setFileExtension('jpg');
        $newMedia->setFileName('filename');
        $productContext->checkForMediaUrl($newMedia);

        $productContextFactory->commit($productContext);

        static::assertCount(1, $posMediaRepoMock->getCollection());
        static::assertCount(1, $posProductRepoMock->getCollection());
        static::assertNotEquals($originalState, $posProductRepoMock->getCollection()->first());

        $productContext->removeProduct($productEntity);
        $productContextFactory->commit($productContext);
        static::assertEmpty($posProductRepoMock->getCollection());
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
        $posMedia = new PosSalesChannelMediaEntity();
        $posMedia->setUrl(self::IMAGE_URL);
        $posMedia->setLookupKey(self::IMAGE_LOOKUP_KEY);
        $posMedia->setMediaId(self::IMAGE_MEDIA_ID_EXISTING);
        $posMedia->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $posMedia->setUniqueIdentifier(Uuid::randomHex());
        $posMediaCollection = new PosSalesChannelMediaCollection([$posMedia]);
        $productContext = new ProductContext($this->getSalesChannel($context), new PosSalesChannelProductCollection(), $posMediaCollection, $context);

        return $productContext;
    }
}

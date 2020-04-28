<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Sync\ChecksumResource;
use Swag\PayPal\Test\Mock\IZettle\IZettleProductRepoMock;

class ChecksumResourceTest extends TestCase
{
    private const SALES_CHANNEL_ID = 'notASalesChannelId';

    public function dataProviderCheckForUpdate(): array
    {
        return [
            ['The name', 'The name', ChecksumResource::PRODUCT_CURRENT],
            ['The old name', 'The new name', ChecksumResource::PRODUCT_OUTDATED],
            [null, 'No name', ChecksumResource::PRODUCT_NEW],
        ];
    }

    /**
     * @dataProvider dataProviderCheckForUpdate
     */
    public function testCheckForUpdate(?string $oldName, string $newName, int $status): void
    {
        $context = Context::createDefaultContext();
        $iZettleProductRepoMock = new IZettleProductRepoMock();

        $productEntity = $this->createProductEntity();
        if ($oldName !== null) {
            $product = new Product();
            $product->setName($oldName);
            $iZettleProductRepoMock->addMockEntity($productEntity, $product, self::SALES_CHANNEL_ID);
        }

        $checksumResource = new ChecksumResource($iZettleProductRepoMock);
        $checksumResource->begin(self::SALES_CHANNEL_ID, $context);

        $product = new Product();
        $product->setName($newName);
        static::assertEquals($status, $checksumResource->checkForUpdate($productEntity, $product));
    }

    public function testCheckForUpdateNotInitialized(): void
    {
        $checksumResource = new ChecksumResource(new IZettleProductRepoMock());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Checksums have not been initialized');
        $checksumResource->checkForUpdate(new ProductEntity(), new Product());
    }

    public function testCommit(): void
    {
        $context = Context::createDefaultContext();

        $iZettleProductRepoMock = $this->createPartialMock(IZettleProductRepoMock::class, ['upsert', 'delete']);
        $checksumResource = new ChecksumResource($iZettleProductRepoMock);
        $checksumResource->begin(self::SALES_CHANNEL_ID, $context);

        $productEntity = $this->createProductEntity();
        $checksumResource->addProduct($productEntity, new Product(), self::SALES_CHANNEL_ID);
        $checksumResource->removeProduct($productEntity, self::SALES_CHANNEL_ID);

        $iZettleProductRepoMock->expects(static::once())->method('upsert');
        $iZettleProductRepoMock->expects(static::once())->method('delete');

        $checksumResource->commit($context);
    }

    private function createProductEntity(): ProductEntity
    {
        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setVersionId(Uuid::randomHex());

        return $productEntity;
    }
}

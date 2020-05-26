<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;
use Swag\PayPal\IZettle\Sync\Context\ProductContextFactory;
use Swag\PayPal\Test\Mock\IZettle\IZettleProductRepoMock;

class ProductContextFactoryTest extends AbstractProductSyncTest
{
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
            if ($productEntity->getVersionId() !== null) {
                $entity->setProductVersionId($productEntity->getVersionId());
            }
            $entity->setUniqueIdentifier(Uuid::randomHex());
            $entity->setChecksum($product->generateChecksum());
            $iZettleProductCollection->add($entity);
        }

        $productContext = new ProductContext($this->createSalesChannel($context), $iZettleProductCollection, $context);

        $product = new Product();
        $product->setName($newName);
        static::assertEquals($status, $productContext->checkForUpdate($productEntity, $product));
    }

    public function testCommit(): void
    {
        $context = Context::createDefaultContext();

        $iZettleProductRepoMock = $this->createPartialMock(IZettleProductRepoMock::class, ['upsert', 'delete']);
        $productContextFactory = new ProductContextFactory($iZettleProductRepoMock);

        $productContext = new ProductContext($this->createSalesChannel($context), new IZettleSalesChannelProductCollection(), $context);

        $productEntity = $this->createProductEntity();
        $productContext->changeProduct($productEntity, new Product());
        $productContext->removeProduct($productEntity);

        $iZettleProductRepoMock->expects(static::once())->method('upsert');
        $iZettleProductRepoMock->expects(static::once())->method('delete');

        $productContextFactory->commit($productContext);
    }

    public function testIdentical(): void
    {
        $context = Context::createDefaultContext();

        $iZettleProductRepoMock = new IZettleProductRepoMock();
        $productContextFactory = new ProductContextFactory($iZettleProductRepoMock);
        $salesChannel = $this->createSalesChannel($context);

        $inventoryContextFirst = $productContextFactory->getContext($salesChannel, $context);
        $inventoryContextSecond = $productContextFactory->getContext($salesChannel, $context);

        static::assertSame($inventoryContextFirst, $inventoryContextSecond);
    }

    private function createProductEntity(): ProductEntity
    {
        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setVersionId(Uuid::randomHex());

        return $productEntity;
    }
}

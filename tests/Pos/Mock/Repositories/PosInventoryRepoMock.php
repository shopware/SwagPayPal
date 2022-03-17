<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryEntity;

class PosInventoryRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new PosSalesChannelInventoryDefinition();
    }

    public function createMockEntity(ProductEntity $productEntity, string $salesChannelId, int $stock): PosSalesChannelInventoryEntity
    {
        $entity = new PosSalesChannelInventoryEntity();
        $entity->setSalesChannelId($salesChannelId);
        $entity->setProductId($productEntity->getId());
        $versionId = $productEntity->getVersionId();
        if ($versionId !== null) {
            $entity->setProductVersionId($versionId);
        }

        $entity->setStock($stock);
        $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));
        $this->addMockEntity($entity);

        return $entity;
    }

    public function filterByProduct(ProductEntity $productEntity): ?PosSalesChannelInventoryEntity
    {
        return $this->entityCollection->filter(
            function (PosSalesChannelInventoryEntity $inventory) use ($productEntity) {
                return $inventory->getProductId() === $productEntity->getId()
                    && $inventory->getProductVersionId() === $productEntity->getVersionId();
            }
        )->first();
    }

    protected function getUniqueIdentifier(Entity $entity): string
    {
        return \implode('-', [
            $entity->get('salesChannelId'),
            $entity->get('productId'),
        ]);
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKeyWrite(Entity $entity): array
    {
        return [
            'salesChannelId' => $entity->get('salesChannelId'),
            'productId' => $entity->get('productId'),
        ];
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKeyRead(Entity $entity): array
    {
        return [
            'sales_channel_id' => $entity->get('salesChannelId'),
            'product_id' => $entity->get('productId'),
        ];
    }
}

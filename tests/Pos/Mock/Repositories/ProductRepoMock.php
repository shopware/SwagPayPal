<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class ProductRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new ProductDefinition();
    }

    public function createMockEntity(string $name, int $stock, int $availableStock, ?string $id = null): ProductEntity
    {
        $entity = new ProductEntity();
        $entity->setId($id ?? Uuid::randomHex());
        $entity->setVersionId(Uuid::randomHex());
        $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));
        $entity->setName($name);
        $entity->setStock($stock);
        $entity->setAvailableStock($availableStock);
        $this->entityCollection->add($entity);

        return $entity;
    }
}

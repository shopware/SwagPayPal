<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\Framework\Uuid\Uuid;

abstract class AbstractRepoMock
{
    /**
     * @var EntityCollection
     */
    protected $entityCollection;

    public function __construct()
    {
        /** @var class-string<EntityCollection> $collectionClass */
        $collectionClass = $this->getDefinition()->getCollectionClass();
        $this->entityCollection = new $collectionClass([]);
    }

    abstract public function getDefinition(): EntityDefinition;

    public function addMockEntity(Entity $entity): void
    {
        $this->entityCollection->add($entity);
    }

    public function getCollection(): EntityCollection
    {
        return $this->entityCollection;
    }

    protected function updateCollection(array $data, Context $context): EntityWrittenContainerEvent
    {
        foreach ($data as $entry) {
            if (!isset($entry['id'])) {
                $entry['id'] = Uuid::randomHex();
            }

            /** @var class-string<Entity> $className */
            $className = $this->getDefinition()->getEntityClass();
            $entity = new $className();
            $entity->assign($entry);
            $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));

            if ($this->entityCollection->has($entity->getUniqueIdentifier())) {
                $entity = $this->entityCollection->get($entity->getUniqueIdentifier()) ?? $entity;
                $entity->assign($entry);
            }

            foreach ($entity->getExtensions() as $name => $extension) {
                if (isset($entry[$name])) {
                    $extension->assign($entry[$name]);
                }
            }

            $this->entityCollection->add($entity);
        }

        return new EntityWrittenContainerEvent($context, new NestedEventCollection([]), []);
    }

    protected function removeFromCollection(array $data, Context $context): EntityWrittenContainerEvent
    {
        foreach ($data as $id) {
            if (\is_array($id)) {
                /** @var class-string<Entity> $className */
                $className = $this->getDefinition()->getEntityClass();
                $entity = new $className();
                $entity->assign($id);
                $id = $this->getUniqueIdentifier($entity);
            }
            $this->entityCollection->remove($id);
        }

        return new EntityWrittenContainerEvent($context, new NestedEventCollection([]), []);
    }

    protected function searchCollectionIds(EntityCollection $entityCollection, Criteria $criteria, Context $context): IdSearchResult
    {
        $repository = $this;

        return new IdSearchResult(
            \count($entityCollection),
            \array_map(static function (Entity $entity) use ($repository) {
                return [
                    'primaryKey' => $entity->has('id') ? $entity->get('id') : $repository->getUniqueIdentifier($entity),
                    'data' => $entity,
                ];
            }, $entityCollection->getElements()),
            $criteria,
            $context
        );
    }

    protected function searchCollection(EntityCollection $entityCollection, Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            \count($entityCollection),
            $entityCollection,
            null,
            $criteria,
            $context
        );
    }

    protected function getUniqueIdentifier(Entity $entity): string
    {
        if ($entity->get('versionId') !== null) {
            return \implode('-', [
                $entity->get('id'),
                $entity->get('versionId'),
            ]);
        }

        return $entity->get('id');
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

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

/**
 * @template T of EntityCollection
 *
 * @internal
 */
trait RepoTrait
{
    /**
     * @var T
     */
    protected EntityCollection $entityCollection;

    public function __construct()
    {
        /** @var class-string<T> $collectionClass */
        $collectionClass = $this->getDefinition()->getCollectionClass();
        // @phpstan-ignore-next-line
        $this->entityCollection = new $collectionClass([]);
    }

    abstract public function getDefinition(): EntityDefinition;

    public function addMockEntity(Entity $entity): void
    {
        $this->entityCollection->add($entity);
    }

    /**
     * @return T
     */
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
        foreach ($data as $primaryKey) {
            foreach ($this->getCollection() as $collectionKey => $element) {
                if (\array_diff($this->getPrimaryKey($element), $primaryKey) === []) {
                    $this->entityCollection->remove($collectionKey);
                }
            }
        }

        return new EntityWrittenContainerEvent($context, new NestedEventCollection([]), []);
    }

    protected function searchCollectionIds(EntityCollection $entityCollection, Criteria $criteria, Context $context): IdSearchResult
    {
        $repository = $this;

        return new IdSearchResult(
            \count($entityCollection),
            \array_map(static function (Entity $entity) use ($repository) {
                $key = $repository->getPrimaryKey($entity);
                if (\count($key) === 1) {
                    $key = \array_pop($key);
                }

                return [
                    'primaryKey' => $key,
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
            $this->getDefinition()->getEntityName(),
            \count($entityCollection),
            $entityCollection,
            null,
            $criteria,
            $context
        );
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKey(Entity $entity): array
    {
        return [
            'id' => $entity->get('id'),
        ];
    }

    protected function getUniqueIdentifier(Entity $entity): string
    {
        if ($entity->get('versionId') !== null) {
            return \implode('-', [
                $entity->get('id'),
                $entity->get('versionId'),
            ]);
        }

        $id = $entity->get('id');
        if (\is_string($id)) {
            return $id;
        }

        throw new \RuntimeException('ID is not a string. Should not happen.');
    }
}

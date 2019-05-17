<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;

class SystemConfigRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new SystemConfigDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
        // TODO: Implement aggregate() method.
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        // TODO: Implement searchIds() method.
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
        // TODO: Implement clone() method.
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        // TODO: Implement search() method.
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        // TODO: Implement update() method.
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
        // TODO: Implement upsert() method.
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
        // TODO: Implement create() method.
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
        // TODO: Implement delete() method.
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
        // TODO: Implement createVersion() method.
    }

    public function merge(string $versionId, Context $context): void
    {
        // TODO: Implement merge() method.
    }
}

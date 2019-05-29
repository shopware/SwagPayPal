<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EntityDefinitionMock extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'entity';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([]);
    }
}

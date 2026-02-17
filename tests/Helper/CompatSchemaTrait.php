<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
trait CompatSchemaTrait
{
    /**
     * @param AbstractSchemaManager<AbstractPlatform> $manager
     * @param non-empty-string $table
     *
     * @throws Exception
     *
     * @return array<string, Column>
     */
    protected function getTableColumns(AbstractSchemaManager $manager, string $table): array
    {
        if (
            !Feature::isActive('v6.8.0.0')
            || !\method_exists($manager, 'introspectTableColumnsByUnquotedName') // @phpstan-ignore function.alreadyNarrowedType
        ) {
            /** @phpstan-ignore method.deprecated */
            return $manager->listTableColumns($table);
        }

        $columns = $manager->introspectTableColumnsByUnquotedName($table);

        $byName = [];
        foreach ($columns as $column) {
            $byName[$column->getObjectName()->toString()] = $column;
        }

        return $byName;
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $manager
     * @param non-empty-string $table
     *
     * @throws Exception
     *
     * @return array<string, Index>
     */
    protected function getTableIndexes(AbstractSchemaManager $manager, string $table): array
    {
        if (
            !Feature::isActive('v6.8.0.0')
            || !\method_exists($manager, 'introspectTableIndexesByUnquotedName') // @phpstan-ignore function.alreadyNarrowedType
        ) {
            /** @phpstan-ignore method.deprecated */
            return $manager->listTableIndexes($table);
        }

        $indexes = $manager->introspectTableIndexesByUnquotedName($table);

        $byName = [];
        foreach ($indexes as $index) {
            $byName[$index->getObjectName()->toString()] = $index;
        }

        return $byName;
    }
}

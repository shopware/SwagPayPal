<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Migration\Migration1706111604AddCustomerIdToVault;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1706111604AddCustomerIdToVault::class)]
class Migration1706111604AddCustomerIdToVaultTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @throws Exception
     */
    public function testMigration(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $connection->rollBack();

        $this->rollback($connection);

        $migration = new Migration1706111604AddCustomerIdToVault();
        $migration->update($connection);
        $migration->update($connection);

        $connection->beginTransaction();

        $manager = $connection->createSchemaManager();

        $columns = $this->getTableColumns($manager, 'swag_paypal_vault_token');
        static::assertCount(8, $columns);
        static::assertArrayHasKey('token_customer', $columns);
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $manager
     * @param non-empty-string $table
     *
     * @throws Exception
     *
     * @return list<Column>|array<string, Column>
     */
    private function getTableColumns(AbstractSchemaManager $manager, string $table): array
    {
        if (
            Feature::isActive('v6.8.0.0')
            && \method_exists($manager, 'introspectTableColumnsByUnquotedName') // @phpstan-ignore function.alreadyNarrowedType
        ) {
            return $manager->introspectTableColumnsByUnquotedName($table);
        }

        /** @phpstan-ignore method.deprecated */
        return $manager->listTableColumns($table);
    }

    /**
     * @throws Exception
     */
    private function rollback(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `swag_paypal_vault_token` DROP COLUMN `token_customer`');
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Migration\Migration1692001928VaultToken;
use Swag\PayPal\Migration\Migration1706111604AddCustomerIdToVault;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1692001928VaultToken::class)]
class Migration1692001928VaultTokenTest extends CompatMigrationTestCase
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

        $migration = new Migration1692001928VaultToken();
        $migration->update($connection);
        $migration->update($connection);

        $manager = $connection->createSchemaManager();

        static::assertTrue($manager->tablesExist(['swag_paypal_vault_token', 'swag_paypal_vault_token_mapping']));

        $vaultTokenColumns = $this->getTableColumns($manager, 'swag_paypal_vault_token');
        static::assertCount(7, $vaultTokenColumns);
        static::assertArrayHasKey('id', $vaultTokenColumns);
        static::assertArrayHasKey('customer_id', $vaultTokenColumns);
        static::assertArrayHasKey('payment_method_id', $vaultTokenColumns);
        static::assertArrayHasKey('token', $vaultTokenColumns);
        static::assertArrayHasKey('identifier', $vaultTokenColumns);
        static::assertArrayHasKey('created_at', $vaultTokenColumns);
        static::assertArrayHasKey('updated_at', $vaultTokenColumns);

        $vaultTokenIndexes = $this->getTableIndexes($manager, 'swag_paypal_vault_token');
        static::assertCount(3, $vaultTokenIndexes);
        static::assertArrayHasKey('primary', $vaultTokenIndexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token.customer_id', $vaultTokenIndexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token.payment_method_id', $vaultTokenIndexes);

        $mappingColumns = $this->getTableColumns($manager, 'swag_paypal_vault_token_mapping');
        static::assertCount(5, $mappingColumns);
        static::assertArrayHasKey('customer_id', $mappingColumns);
        static::assertArrayHasKey('payment_method_id', $mappingColumns);
        static::assertArrayHasKey('token_id', $mappingColumns);
        static::assertArrayHasKey('created_at', $mappingColumns);
        static::assertArrayHasKey('updated_at', $mappingColumns);

        $mappingIndexes = $this->getTableIndexes($manager, 'swag_paypal_vault_token_mapping');
        static::assertCount(3, $mappingIndexes);
        static::assertArrayHasKey('primary', $mappingIndexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token_mapping.payment_method_id', $mappingIndexes);
        static::assertArrayHasKey('uniq.swag_paypal_vault_token_mapping.token_id', $mappingIndexes);

        (new Migration1706111604AddCustomerIdToVault())->update($connection);
        $connection->beginTransaction();
    }

    /**
     * @throws Exception
     */
    private function rollback(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_vault_token_mapping`');
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_vault_token`');
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
class Migration1692001928VaultTokenTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

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

        $columns = $manager->listTableColumns('swag_paypal_vault_token');

        static::assertCount(7, $columns);
        static::assertArrayHasKey('id', $columns);
        static::assertArrayHasKey('customer_id', $columns);
        static::assertArrayHasKey('payment_method_id', $columns);
        static::assertArrayHasKey('token', $columns);
        static::assertArrayHasKey('identifier', $columns);
        static::assertArrayHasKey('created_at', $columns);
        static::assertArrayHasKey('updated_at', $columns);

        $indexes = $manager->listTableIndexes('swag_paypal_vault_token');

        static::assertCount(3, $indexes);
        static::assertArrayHasKey('primary', $indexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token.customer_id', $indexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token.payment_method_id', $indexes);

        $columns = $manager->listTableColumns('swag_paypal_vault_token_mapping');

        static::assertCount(5, $columns);
        static::assertArrayHasKey('customer_id', $columns);
        static::assertArrayHasKey('payment_method_id', $columns);
        static::assertArrayHasKey('token_id', $columns);
        static::assertArrayHasKey('created_at', $columns);
        static::assertArrayHasKey('updated_at', $columns);

        $indexes = $manager->listTableIndexes('swag_paypal_vault_token_mapping');

        static::assertCount(3, $indexes);
        static::assertArrayHasKey('primary', $indexes);
        static::assertArrayHasKey('fk.swag_paypal_vault_token_mapping.payment_method_id', $indexes);
        static::assertArrayHasKey('uniq.swag_paypal_vault_token_mapping.token_id', $indexes);

        (new Migration1706111604AddCustomerIdToVault())->update($connection);
        $connection->beginTransaction();
    }

    private function rollback(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_vault_token_mapping`');
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_vault_token`');
    }
}

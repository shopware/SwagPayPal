<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Migration\Migration1706111604AddCustomerIdToVault;

/**
 * @covers \Swag\PayPal\Migration\Migration1706111604AddCustomerIdToVault
 *
 * @internal
 */
#[Package('checkout')]
class Migration1706111604AddCustomerIdToVaultTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

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

        $columns = $manager->listTableColumns('swag_paypal_vault_token');

        static::assertCount(8, $columns);
        static::assertArrayHasKey('token_customer', $columns);
    }

    private function rollback(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `swag_paypal_vault_token` DROP COLUMN `token_customer`');
    }
}

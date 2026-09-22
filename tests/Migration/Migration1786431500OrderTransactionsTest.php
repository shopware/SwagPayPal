<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;
use Swag\PayPal\Test\Helper\CompatSchemaTrait;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1786431500OrderTransactions::class)]
class Migration1786431500OrderTransactionsTest extends TestCase
{
    use CompatSchemaTrait;

    public function testCreatesTableIdempotently(): void
    {
        $connection = KernelLifecycleManager::getConnection();
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_order_transactions`');

        $migration = new Migration1786431500OrderTransactions();

        $migration->update($connection);
        $migration->update($connection);

        $schemaManager = $connection->createSchemaManager();

        static::assertTrue($schemaManager->tablesExist(['swag_paypal_order_transactions']));
        $columns = $this->getTableColumns($schemaManager, 'swag_paypal_order_transactions');
        static::assertArrayHasKey('order_transaction_id', $columns);
        static::assertArrayHasKey('paypal_order_id', $columns);

        $primaryKey = $connection->fetchAssociative('SHOW INDEX FROM `swag_paypal_order_transactions` WHERE `Key_name` = \'PRIMARY\'');
        static::assertIsArray($primaryKey);
        static::assertSame('paypal_order_id', $primaryKey['Column_name']);
    }
}

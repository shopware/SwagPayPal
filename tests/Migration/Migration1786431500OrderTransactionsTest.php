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
use Shopware\Core\Framework\Util\Database\TableHelper;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1786431500OrderTransactions::class)]
class Migration1786431500OrderTransactionsTest extends TestCase
{
    protected function tearDown(): void
    {
        KernelLifecycleManager::getConnection()->executeStatement('DROP TABLE IF EXISTS `swag_paypal_order_transactions`');
    }

    public function testCreatesTableIdempotently(): void
    {
        $connection = KernelLifecycleManager::getConnection();
        $migration = new Migration1786431500OrderTransactions();

        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue(TableHelper::tableExists($connection, 'swag_paypal_order_transactions'));
        static::assertTrue(TableHelper::columnExists($connection, 'swag_paypal_order_transactions', 'order_transaction_id'));
        static::assertTrue(TableHelper::columnExists($connection, 'swag_paypal_order_transactions', 'paypal_order_id'));

        $primaryKey = $connection->fetchAssociative('SHOW INDEX FROM `swag_paypal_order_transactions` WHERE `Key_name` = \'PRIMARY\'');
        static::assertIsArray($primaryKey);
        static::assertSame('paypal_order_id', $primaryKey['Column_name']);
    }
}

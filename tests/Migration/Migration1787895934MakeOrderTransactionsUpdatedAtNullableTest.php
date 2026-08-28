<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;
use Swag\PayPal\Migration\Migration1787895934MakeOrderTransactionsUpdatedAtNullable;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1787895934MakeOrderTransactionsUpdatedAtNullableTest extends TestCase
{
    public function testMakesUpdatedAtNullableIdempotently(): void
    {
        $connection = KernelLifecycleManager::getConnection();
        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_order_transactions`');

        (new Migration1786431500OrderTransactions())->update($connection);
        $connection->executeStatement(
            'ALTER TABLE `swag_paypal_order_transactions` MODIFY COLUMN `updated_at` DATETIME(3) NOT NULL'
        );

        $migration = new Migration1787895934MakeOrderTransactionsUpdatedAtNullable();
        $migration->update($connection);
        $migration->update($connection);

        $column = $connection->fetchAssociative(
            'SHOW COLUMNS FROM `swag_paypal_order_transactions` WHERE `Field` = \'updated_at\''
        );

        static::assertIsArray($column);
        static::assertSame('YES', $column['Null']);
    }
}

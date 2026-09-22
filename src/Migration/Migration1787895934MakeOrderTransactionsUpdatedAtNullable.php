<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1787895934MakeOrderTransactionsUpdatedAtNullable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1787895934;
    }

    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['swag_paypal_order_transactions'])) {
            return;
        }

        if (!$this->columnExists($connection, 'swag_paypal_order_transactions', 'updated_at')) {
            return;
        }

        $connection->executeStatement(
            'ALTER TABLE `swag_paypal_order_transactions` MODIFY COLUMN `updated_at` DATETIME(3) NULL'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

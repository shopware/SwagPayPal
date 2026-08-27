<?php declare(strict_types=1);

namespace Swag\PayPal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('checkout')]
class Migration1786431500OrderTransactions extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1786431500;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `swag_paypal_order_transactions` (
                `order_transaction_id`         BINARY(16)     NOT NULL,
                `order_transaction_version_id` BINARY(16)     NOT NULL,
                `paypal_order_id`              VARCHAR(255)   NOT NULL,
                `created_at`                   DATETIME(3)    NOT NULL,
                `updated_at`                   DATETIME(3)    NULL,
                PRIMARY KEY (`paypal_order_id`),
                KEY `idx.swag_paypal_order_transactions.created_at` (`created_at`),
                CONSTRAINT `fk.swag_paypal_order_transactions.order_transaction_id`
                    FOREIGN KEY (`order_transaction_id`, `order_transaction_version_id`)
                    REFERENCES `order_transaction` (`id`, `version_id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

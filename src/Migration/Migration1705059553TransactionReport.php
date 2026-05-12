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
class Migration1705059553TransactionReport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1705059553;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_transaction_report` (
                `order_transaction_id`         BINARY(16)     NOT NULL,
                `order_transaction_version_id` BINARY(16)     NOT NULL,
                `currency_iso`                 VARCHAR(3)     NOT NULL,
                `total_price`                  DECIMAL(20, 2) NOT NULL,
                `created_at`                   DATETIME(3)    NOT NULL,
                `updated_at`                   DATETIME(3)    NULL,
                PRIMARY KEY (`order_transaction_id`, `order_transaction_version_id`),
                CONSTRAINT `fk.swag_paypal_transaction_report.order_transaction_id` FOREIGN KEY (`order_transaction_id`, `order_transaction_version_id`) REFERENCES `order_transaction` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

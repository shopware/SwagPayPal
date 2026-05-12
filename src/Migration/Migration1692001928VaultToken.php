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
class Migration1692001928VaultToken extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692001928;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_vault_token` (
                `id`                BINARY(16)      NOT NULL,
                `customer_id`       BINARY(16)      NOT NULL,
                `payment_method_id` BINARY(16)      NOT NULL,
                `token`             VARCHAR(255)    NOT NULL,
                `identifier`        VARCHAR(255)    NOT NULL,
                `created_at`        DATETIME(3)     NOT NULL,
                `updated_at`        DATETIME(3)     NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.swag_paypal_vault_token.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_vault_token.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_vault_token_mapping` (
                `customer_id`       BINARY(16)      NOT NULL,
                `payment_method_id` BINARY(16)      NOT NULL,
                `token_id`          BINARY(16)      NOT NULL,
                `created_at`        DATETIME(3)     NOT NULL,
                `updated_at`        DATETIME(3)     NULL,
                PRIMARY KEY (`customer_id`, `payment_method_id`),
                CONSTRAINT `uniq.swag_paypal_vault_token_mapping.token_id` UNIQUE (`token_id`),
                CONSTRAINT `fk.swag_paypal_vault_token_mapping.token_id` FOREIGN KEY (`token_id`) REFERENCES `swag_paypal_vault_token` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_vault_token_mapping.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_vault_token_mapping.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

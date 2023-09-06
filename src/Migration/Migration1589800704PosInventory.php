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
class Migration1589800704PosInventory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589800704;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_pos_sales_channel_inventory` (
                `sales_channel_id`   BINARY(16)  NOT NULL,
                `product_id`         BINARY(16)  NOT NULL,
                `product_version_id` BINARY(16)  NOT NULL,
                `stock`              INT         NOT NULL,
                `updated_at`         DATETIME(3) NULL,
                PRIMARY KEY (`sales_channel_id`, `product_id`, `product_version_id`),
                KEY `fk.swag_paypal_pos_sales_channel_inventory.sales_channel_id` (`sales_channel_id`),
                KEY `fk.swag_paypal_pos_sales_channel_inventory.product_id` (`product_id`),
                KEY `fk.swag_paypal_pos_sales_channel_inventory.product_vid` (`product_version_id`),
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_inventory.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_inventory.product` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

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
class Migration1589905764PosRunLog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589905764;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_pos_sales_channel_run_log` (
                `id`                 BINARY(16)  NOT NULL,
                `run_id`             BINARY(16)  NOT NULL,
                `level`              SMALLINT    NOT NULL,
                `message`            LONGTEXT    NOT NULL,
                `product_id`         BINARY(16)  NULL,
                `product_version_id` BINARY(16)  NULL,
                `created_at`         DATETIME(3) NOT NULL,
                `updated_at`         DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.swag_paypal_pos_sales_channel_run_log.run_id` (`run_id`),
                KEY `fk.swag_paypal_pos_sales_channel_run_log.product_id` (`product_id`),
                KEY `fk.swag_paypal_pos_sales_channel_run_log.product_vid` (`product_version_id`),
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_run_log.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_paypal_pos_sales_channel_run` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_run_log.product` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE SET NULL ON UPDATE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

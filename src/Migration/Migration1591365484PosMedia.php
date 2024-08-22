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
class Migration1591365484PosMedia extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1591365484;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `swag_paypal_pos_sales_channel_media` (
                `sales_channel_id`   BINARY(16)   NOT NULL,
                `media_id`           BINARY(16)   NOT NULL,
                `lookup_key`         VARCHAR(255) NULL,
                `url`                VARCHAR(255) NULL,
                `created_at`         DATETIME(3)  NOT NULL,
                PRIMARY KEY (`sales_channel_id`, `media_id`),
                KEY `fk.swag_paypal_pos_sales_channel_media.sales_channel_id` (`sales_channel_id`),
                KEY `fk.swag_paypal_pos_sales_channel_media.media_id` (`media_id`),
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_media.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_paypal_pos_sales_channel_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536761533SettingGeneral extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536761533;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_paypal_setting_general` (
    `id`                    BINARY(16)  NOT NULL,
    `client_id`             VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `client_secret`         VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `sandbox`               TINYINT(1)  NOT NULL,
    `submit_cart`           TINYINT(1)  NOT NULL,
    `webhook_id`            VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `webhook_execute_token` VARCHAR(32) COLLATE utf8mb4_unicode_ci,
    `brand_name`            VARCHAR(127) COLLATE utf8mb4_unicode_ci,
    `created_at`            DATETIME(3) NOT NULL,
    `updated_at`            DATETIME(3),
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

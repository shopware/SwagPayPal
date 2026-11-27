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
class Migration1795187213PayPalLog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1795187213;
    }

    public function update(Connection $connection): void
    {
        // no foreign keys, lines outlive the Sales Channel or order they name
        // JSON_UNQUOTE returns a JSON null as 'null'
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `swag_paypal_log` (
                `id`               BINARY(16)        NOT NULL,
                `level`            SMALLINT UNSIGNED NOT NULL,
                `message`          LONGTEXT          NOT NULL,
                `context`          JSON              NULL,
                `extra`            JSON              NULL,
                `created_at`       DATETIME(3)       NOT NULL,
                `updated_at`       DATETIME(3)       NULL,
                `code`             VARCHAR(255)      GENERATED ALWAYS AS (NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`context`, '$.code')), 'null')) STORED,
                `sales_channel_id` BINARY(16)        GENERATED ALWAYS AS (UNHEX(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`context`, '$.salesChannelId')), 'null'))) STORED,
                `uid`              VARCHAR(32)       GENERATED ALWAYS AS (NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.uid')), 'null')) STORED,
                PRIMARY KEY (`id`),
                KEY `idx.swag_paypal_log.created_at` (`created_at`),
                KEY `idx.swag_paypal_log.code` (`code`),
                KEY `idx.swag_paypal_log.sales_channel_id.created_at` (`sales_channel_id`, `created_at`),
                KEY `idx.swag_paypal_log.uid` (`uid`),
                CONSTRAINT `json.swag_paypal_log.context` CHECK (JSON_VALID(`context`)),
                CONSTRAINT `json.swag_paypal_log.extra` CHECK (JSON_VALID(`extra`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL);

        $this->addAdditionalPrivileges($connection, ['swag_paypal.viewer' => ['swag_paypal_log:read']]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

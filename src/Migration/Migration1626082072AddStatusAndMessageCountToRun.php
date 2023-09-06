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
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1626082072AddStatusAndMessageCountToRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1626082072;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, PosSalesChannelRunDefinition::ENTITY_NAME, 'status')
         || $this->columnExists($connection, PosSalesChannelRunDefinition::ENTITY_NAME, 'message_count')) {
            return;
        }

        $sql = <<<SQL
            ALTER TABLE `#table#`
                ADD `status` VARCHAR(255) DEFAULT '#default_status#' NOT NULL,
                ADD `message_count` INT DEFAULT 0 NOT NULL;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#default_status#'],
            [PosSalesChannelRunDefinition::ENTITY_NAME, PosSalesChannelRunDefinition::STATUS_IN_PROGRESS],
            $sql
        ));

        $sql = <<<SQL
            UPDATE `#table#`
                SET `status` = '#status#'
                WHERE `finished_at` IS NOT NULL;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#status#'],
            [PosSalesChannelRunDefinition::ENTITY_NAME, PosSalesChannelRunDefinition::STATUS_FINISHED],
            $sql
        ));

        $sql = <<<SQL
            UPDATE `#table#`
                SET `status` = '#status#'
                WHERE `aborted_by_user` = 1;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#status#'],
            [PosSalesChannelRunDefinition::ENTITY_NAME, PosSalesChannelRunDefinition::STATUS_CANCELLED],
            $sql
        ));

        $sql = <<<SQL
            ALTER TABLE `#table#` DROP `aborted_by_user`;
SQL;

        $connection->executeStatement(\str_replace(['#table#'], [PosSalesChannelRunDefinition::ENTITY_NAME], $sql));
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

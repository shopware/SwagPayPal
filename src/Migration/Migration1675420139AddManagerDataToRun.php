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
class Migration1675420139AddManagerDataToRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1675420139;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, PosSalesChannelRunDefinition::ENTITY_NAME, 'step_index')
            || $this->columnExists($connection, PosSalesChannelRunDefinition::ENTITY_NAME, 'steps')) {
            return;
        }

        $sql = <<<SQL
            ALTER TABLE `#table#`
                ADD `step_index` VARCHAR(255) DEFAULT 0 NOT NULL,
                ADD `steps` JSON NOT NULL;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#'],
            [PosSalesChannelRunDefinition::ENTITY_NAME],
            $sql
        ));
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

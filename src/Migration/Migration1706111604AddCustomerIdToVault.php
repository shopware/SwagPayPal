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
class Migration1706111604AddCustomerIdToVault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1706111604;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'swag_paypal_vault_token', 'token_customer')) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `swag_paypal_vault_token`
            ADD COLUMN `token_customer` VARCHAR(255) NULL AFTER `token`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

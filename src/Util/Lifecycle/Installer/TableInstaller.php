<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingDefinition;
use Swag\PayPal\Reporting\DataAbstractionLayer\TransactionReport\TransactionReportDefinition;

/**
 * @internal
 */
#[Package('checkout')]
class TableInstaller
{
    // the mapping references the token, so it has to be dropped first
    private const TABLES = [
        VaultTokenMappingDefinition::ENTITY_NAME,
        VaultTokenDefinition::ENTITY_NAME,
        TransactionReportDefinition::ENTITY_NAME,
        'swag_paypal_order_transactions',
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function removeTables(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->executeStatement(\sprintf('DROP TABLE IF EXISTS `%s`', $table));
        }
    }
}

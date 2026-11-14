<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Installer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Swag\PayPal\Util\Lifecycle\Installer\TableInstaller;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TableInstaller::class)]
class TableInstallerTest extends TestCase
{
    private const TABLES = [
        'swag_paypal_vault_token',
        'swag_paypal_vault_token_mapping',
        'swag_paypal_transaction_report',
        'swag_paypal_order_transactions',
    ];

    public function testRemoveTablesDropsThePluginTables(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $createStatements = [];
        foreach (self::TABLES as $table) {
            $createStatement = $connection->fetchAssociative(\sprintf('SHOW CREATE TABLE `%s`', $table))['Create Table'] ?? null;
            static::assertIsString($createStatement);
            $createStatements[] = $createStatement;
        }

        try {
            (new TableInstaller($connection))->removeTables();

            $schemaManager = $connection->createSchemaManager();
            foreach (self::TABLES as $table) {
                static::assertFalse($schemaManager->tablesExist([$table]), $table);
            }
        } finally {
            foreach ($createStatements as $createStatement) {
                $connection->executeStatement(\str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $createStatement));
            }
        }
    }
}

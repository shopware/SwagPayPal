<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Migration\Migration1795187213PayPalLog;
use Swag\PayPal\Test\Helper\CompatSchemaTrait;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1795187213PayPalLog::class)]
class Migration1795187213PayPalLogTest extends TestCase
{
    use CompatSchemaTrait;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @throws Exception
     */
    public function testMigration(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $connection->rollBack();

        $connection->executeStatement('DROP TABLE IF EXISTS `swag_paypal_log`');

        $migration = new Migration1795187213PayPalLog();
        $migration->update($connection);
        $migration->update($connection);

        $connection->beginTransaction();

        $manager = $connection->createSchemaManager();

        static::assertSame(
            ['id', 'level', 'message', 'context', 'extra', 'created_at', 'updated_at', 'code', 'sales_channel_id', 'uid'],
            \array_keys($this->getTableColumns($manager, 'swag_paypal_log')),
        );
        static::assertEqualsCanonicalizing(
            ['primary', 'idx.swag_paypal_log.created_at', 'idx.swag_paypal_log.code', 'idx.swag_paypal_log.sales_channel_id.created_at', 'idx.swag_paypal_log.uid'],
            \array_keys($this->getTableIndexes($manager, 'swag_paypal_log')),
        );

        $generated = $connection->fetchFirstColumn(
            'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = :table AND `EXTRA` LIKE :extra',
            ['table' => 'swag_paypal_log', 'extra' => '%STORED GENERATED%'],
        );
        static::assertEqualsCanonicalizing(['code', 'sales_channel_id', 'uid'], $generated);
    }

    public function testMigrationGrantsTheViewerRoleTheLogs(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        // the migration manages its own transaction
        $connection->rollBack();

        $viewerId = Uuid::randomBytes();
        $otherId = Uuid::randomBytes();

        try {
            $connection->insert('acl_role', ['id' => $viewerId, 'name' => 'paypal viewer', 'privileges' => \json_encode(['swag_paypal.viewer'], \JSON_THROW_ON_ERROR), 'created_at' => '2026-11-20 10:00:00.000']);
            $connection->insert('acl_role', ['id' => $otherId, 'name' => 'other', 'privileges' => \json_encode(['product:read'], \JSON_THROW_ON_ERROR), 'created_at' => '2026-11-20 10:00:00.000']);

            (new Migration1795187213PayPalLog())->update($connection);

            static::assertSame(['swag_paypal.viewer', 'swag_paypal_log:read'], $this->getPrivileges($connection, $viewerId));
            static::assertSame(['product:read'], $this->getPrivileges($connection, $otherId));
        } finally {
            $connection->executeStatement('DELETE FROM `acl_role` WHERE `id` IN (:ids)', ['ids' => [$viewerId, $otherId]], ['ids' => ArrayParameterType::BINARY]);
            $connection->beginTransaction();
        }
    }

    /**
     * @return list<string>
     */
    private function getPrivileges(Connection $connection, string $roleId): array
    {
        $privileges = \json_decode((string) $connection->fetchOne('SELECT `privileges` FROM `acl_role` WHERE `id` = :id', ['id' => $roleId]), true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($privileges);
        \sort($privileges);

        return $privileges;
    }
}

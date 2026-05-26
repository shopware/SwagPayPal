<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Migration\Migration1752337399AddAgenticCommerceSalesChannelType;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1752337399AddAgenticCommerceSalesChannelTypeTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testMigration(): void
    {
        $connection = $this->getContainer()->get(Connection::class);

        $this->rollback($connection);

        $migration = new Migration1752337399AddAgenticCommerceSalesChannelType();
        $migration->update($connection);
        $migration->update($connection);

        $type = $connection->fetchOne(
            'SELECT `id` FROM `sales_channel_type` WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE)]
        );

        static::assertNotFalse($type);

        $translations = $connection->fetchAllAssociative(
            'SELECT `sales_channel_type_id`, `language_id`, `name` FROM `sales_channel_type_translation` WHERE `sales_channel_type_id` = :id',
            ['id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE)]
        );

        static::assertCount(2, $translations);
    }

    public function rollback(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `sales_channel_type` WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE)]
        );
    }
}

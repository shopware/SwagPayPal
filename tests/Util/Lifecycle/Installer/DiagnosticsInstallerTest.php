<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Installer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Util\Lifecycle\Installer\DiagnosticsInstaller;

/**
 * @internal
 */
#[Package('checkout')]
class DiagnosticsInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testRemovesOnlyTheRecordedCallbacks(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $recorded = LastCallbackStorage::KEY_PREFIX . '98432def39fc4624b33213a56b8c944d';
        // Would match the prefix if its underscores weren't escaped
        $similar = 'swagXpaypal.express_callback.98432def39fc4624b33213a56b8c944d';
        $connection->insert('app_config', ['`key`' => $recorded, 'value' => '{}']);
        $connection->insert('app_config', ['`key`' => $similar, 'value' => '{}']);

        (new DiagnosticsInstaller($connection))->removeDiagnosticsData();

        $keys = $connection->fetchFirstColumn('SELECT `key` FROM `app_config` WHERE `key` IN (:keys)', ['keys' => [$recorded, $similar]], ['keys' => ArrayParameterType::STRING]);
        static::assertSame([$similar], $keys);
    }
}

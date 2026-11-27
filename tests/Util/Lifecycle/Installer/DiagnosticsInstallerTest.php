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
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Util\Lifecycle\Installer\DiagnosticsInstaller;

/**
 * @internal
 */
#[Package('checkout')]
class DiagnosticsInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testRemovesOnlyTheRecordedCallbacksWebhooksAndTestWebhooks(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $recorded = LastCallbackStorage::KEY_PREFIX . '98432def39fc4624b33213a56b8c944d';
        // Would match the prefix if its underscores weren't escaped
        $similar = 'swagXpaypal.express_callback.98432def39fc4624b33213a56b8c944d';
        $recordedWebhook = LastWebhookStorage::KEY_PREFIX . \hash('xxh128', 'a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $similarWebhook = 'swagXpaypal.webhook.' . \hash('xxh128', 'a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $connection->insert('app_config', ['`key`' => $recorded, 'value' => '{}']);
        $connection->insert('app_config', ['`key`' => $similar, 'value' => '{}']);
        $connection->insert('app_config', ['`key`' => $recordedWebhook, 'value' => '{}']);
        $connection->insert('app_config', ['`key`' => $similarWebhook, 'value' => '{}']);
        $pendingTest = TestWebhookStorage::KEY_PREFIX . 'pending.' . \hash('xxh128', 'a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $arrivedTest = TestWebhookStorage::KEY_PREFIX . 'arrival.' . \hash('xxh128', 'a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $connection->insert('app_config', ['`key`' => $pendingTest, 'value' => '{}']);
        $connection->insert('app_config', ['`key`' => $arrivedTest, 'value' => '{}']);

        (new DiagnosticsInstaller($connection))->removeDiagnosticsData();

        $keys = $connection->fetchFirstColumn('SELECT `key` FROM `app_config` WHERE `key` IN (:keys)', ['keys' => [$recorded, $similar, $recordedWebhook, $similarWebhook, $pendingTest, $arrivedTest]], ['keys' => ArrayParameterType::STRING]);
        static::assertEqualsCanonicalizing([$similar, $similarWebhook], $keys);
    }
}

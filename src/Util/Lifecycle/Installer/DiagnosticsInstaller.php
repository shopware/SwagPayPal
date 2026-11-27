<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;

/**
 * @internal
 */
#[Package('checkout')]
class DiagnosticsInstaller
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function removeDiagnosticsData(): void
    {
        foreach ([LastCallbackStorage::KEY_PREFIX, LastWebhookStorage::KEY_PREFIX, TestWebhookStorage::KEY_PREFIX] as $prefix) {
            $this->connection->executeStatement(
                'DELETE FROM `app_config` WHERE `key` LIKE :prefix',
                ['prefix' => \addcslashes($prefix, '%_') . '%'],
            );
        }
    }
}

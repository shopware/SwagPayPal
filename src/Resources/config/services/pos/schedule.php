<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Schedule\AbstractSyncTaskHandler;
use Swag\PayPal\Pos\Schedule\CleanUpLogTask;
use Swag\PayPal\Pos\Schedule\CleanUpLogTaskHandler;
use Swag\PayPal\Pos\Schedule\CompleteSyncTask;
use Swag\PayPal\Pos\Schedule\CompleteSyncTaskHandler;
use Swag\PayPal\Pos\Schedule\InventorySyncTask;
use Swag\PayPal\Pos\Schedule\InventorySyncTaskHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CleanUpLogTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(CompleteSyncTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(InventorySyncTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(AbstractSyncTaskHandler::class)
        ->abstract()
        ->args([
            service('scheduled_task.repository'),
            service('monolog.logger.paypal'),
            service('sales_channel.repository'),
        ]);

    $services->set(CleanUpLogTaskHandler::class)
        ->parent(AbstractSyncTaskHandler::class)
        ->args([
            service(LogCleaner::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(CompleteSyncTaskHandler::class)
        ->parent(AbstractSyncTaskHandler::class)
        ->args([
            service(CompleteTask::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(InventorySyncTaskHandler::class)
        ->parent(AbstractSyncTaskHandler::class)
        ->args([
            service(InventoryTask::class),
        ])
        ->tag('messenger.message_handler');
};

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\Pos\Command\PosImageSyncCommand;
use Swag\PayPal\Pos\Command\PosInventorySyncCommand;
use Swag\PayPal\Pos\Command\PosLogCleanupCommand;
use Swag\PayPal\Pos\Command\PosProductSyncCommand;
use Swag\PayPal\Pos\Command\PosSyncCommand;
use Swag\PayPal\Pos\PosSyncController;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PosSyncController::class)
        ->public()
        ->args([
            service('sales_channel.repository'),
            service(CompleteTask::class),
            service(ProductTask::class),
            service(ImageTask::class),
            service(InventoryTask::class),
            service(LogCleaner::class),
            service(RunService::class),
            service(SyncResetter::class),
            service(ProductSelection::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(PosSyncCommand::class)
        ->args([
            service('sales_channel.repository'),
            service(CompleteTask::class),
        ])
        ->tag('console.command');

    $services->set(PosImageSyncCommand::class)
        ->args([
            service('sales_channel.repository'),
            service(ImageTask::class),
        ])
        ->tag('console.command');

    $services->set(PosInventorySyncCommand::class)
        ->args([
            service('sales_channel.repository'),
            service(InventoryTask::class),
        ])
        ->tag('console.command');

    $services->set(PosProductSyncCommand::class)
        ->args([
            service('sales_channel.repository'),
            service(ProductTask::class),
        ])
        ->tag('console.command');

    $services->set(PosLogCleanupCommand::class)
        ->args([
            service('sales_channel.repository'),
            service(LogCleaner::class),
        ])
        ->tag('console.command');
};

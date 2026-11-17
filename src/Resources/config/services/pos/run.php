<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Psr\Clock\ClockInterface;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;
use Swag\PayPal\Pos\Run\LoggerFactory;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\AbstractTask;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(AbstractTask::class)
        ->abstract()
        ->args([
            service(MessageDispatcher::class),
            service(RunService::class),
        ]);

    $services->set(CompleteTask::class)
        ->parent(AbstractTask::class);

    $services->set(ProductTask::class)
        ->parent(AbstractTask::class);

    $services->set(ImageTask::class)
        ->parent(AbstractTask::class);

    $services->set(InventoryTask::class)
        ->parent(AbstractTask::class);

    $services->set(LoggerFactory::class);

    $services->set('Swag\PayPal\Pos\Run\Logger', Logger::class)
        ->factory([service(LoggerFactory::class), 'createLogger']);

    $services->set(RunService::class)
        ->args([
            service('swag_paypal_pos_sales_channel_run.repository'),
            service('swag_paypal_pos_sales_channel_run_log.repository'),
            service(Connection::class),
            service('Swag\PayPal\Pos\Run\Logger'),
            service(ClockInterface::class),
        ]);

    $services->set(LogCleaner::class)
        ->args([
            service('swag_paypal_pos_sales_channel_run.repository'),
            service(ClockInterface::class),
        ]);

    $services->set(SyncResetter::class)
        ->args([
            service('swag_paypal_pos_sales_channel_product.repository'),
            service('swag_paypal_pos_sales_channel_inventory.repository'),
            service('swag_paypal_pos_sales_channel_media.repository'),
            service('swag_paypal_pos_sales_channel_run.repository'),
        ]);
};

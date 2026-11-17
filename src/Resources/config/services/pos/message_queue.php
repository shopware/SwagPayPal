<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Swag\PayPal\Pos\MessageQueue\Handler\CloneVisibilityHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\InventoryUpdateHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\AbstractSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ImageSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\InventorySyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductCleanupSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductSingleSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductVariantSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\AbstractSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CloneVisibilityHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service('product_visibility.repository'),
        ])
        ->tag('messenger.message_handler');

    $services->set(SyncManagerHandler::class)
        ->args([
            service(MessageDispatcher::class),
            service(MessageHydrator::class),
            service(RunService::class),
            service('Swag\PayPal\Pos\Run\Logger'),
            service(ImageSyncManager::class),
            service(InventorySyncManager::class),
            service(ProductSyncManager::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(AbstractSyncManager::class)
        ->abstract()
        ->args([
            service(MessageDispatcher::class),
        ]);

    $services->set(ImageSyncManager::class)
        ->parent(AbstractSyncManager::class)
        ->args([
            service('swag_paypal_pos_sales_channel_media.repository'),
            service(ImageSyncer::class),
        ]);

    $services->set(InventorySyncManager::class)
        ->parent(AbstractSyncManager::class)
        ->args([
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service(InventoryContextFactory::class),
            param('shopware.stock.enable_stock_management'),
        ]);

    $services->set(ProductSyncManager::class)
        ->parent(AbstractSyncManager::class)
        ->args([
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service(ImageSyncer::class),
        ]);

    $services->set(AbstractSyncHandler::class)
        ->abstract()
        ->args([
            service(RunService::class),
            service('Swag\PayPal\Pos\Run\Logger'),
            service(MessageDispatcher::class),
            service(MessageHydrator::class),
        ]);

    $services->set(ProductSingleSyncHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service(ProductSyncer::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(ProductVariantSyncHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service(ProductSyncer::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(ProductCleanupSyncHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service(ProductSyncer::class),
            service('swag_paypal_pos_sales_channel.repository'),
        ])
        ->tag('messenger.message_handler');

    $services->set(InventorySyncHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service('product.repository'),
            service(InventoryContextFactory::class),
            service(InventorySyncer::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(ImageSyncHandler::class)
        ->parent(AbstractSyncHandler::class)
        ->args([
            service('swag_paypal_pos_sales_channel_media.repository'),
            service(ImageSyncer::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(InventoryUpdateHandler::class)
        ->args([
            service(RunService::class),
            service('sales_channel.repository'),
            service(InventorySyncManager::class),
            service(MessageDispatcher::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(MessageDispatcher::class)
        ->args([
            service('messenger.default_bus'),
            service(Connection::class),
            service(ClockInterface::class),
        ]);

    $services->set(MessageHydrator::class)
        ->args([
            service(SalesChannelContextService::class),
            service('sales_channel.repository'),
        ]);
};

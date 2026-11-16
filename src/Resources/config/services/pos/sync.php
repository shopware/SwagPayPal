<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\MediaConverter;
use Swag\PayPal\Pos\Api\Service\ProductConverter;
use Swag\PayPal\Pos\Resource\ImageResource;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Context\ProductContextFactory;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalCalculator;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalWebhookCalculator;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\RemoteCalculator;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\Pos\Sync\Inventory\StockSubscriber;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Sync\Product\DeletedUpdater;
use Swag\PayPal\Pos\Sync\Product\NewUpdater;
use Swag\PayPal\Pos\Sync\Product\OutdatedUpdater;
use Swag\PayPal\Pos\Sync\Product\UnsyncedChecker;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(ProductSelection::class)
        ->args([
            service('sales_channel.product.repository'),
            service(ProductStreamBuilder::class),
            service(SalesChannelContextFactory::class),
        ]);

    $services->set(ImageSyncer::class)
        ->args([
            service('swag_paypal_pos_sales_channel_media.repository'),
            service(MediaConverter::class),
            service(ImageResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(RemoteCalculator::class)
        ->args([
            service(UuidConverter::class),
        ]);

    $services->set(LocalCalculator::class);

    $services->set(LocalWebhookCalculator::class);

    $services->set(InventoryContextFactory::class)
        ->args([
            service(InventoryResource::class),
            service(UuidConverter::class),
            service('swag_paypal_pos_sales_channel_inventory.repository'),
        ]);

    $services->set('swag.paypal.pos.sync.inventory.local_updater', LocalUpdater::class)
        ->args([
            service('product.repository'),
            service(LocalCalculator::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set('swag.paypal.pos.sync.inventory.local_webhook_updater', LocalUpdater::class)
        ->args([
            service('product.repository'),
            service(LocalWebhookCalculator::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(RemoteUpdater::class)
        ->args([
            service(InventoryResource::class),
            service(RemoteCalculator::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(InventorySyncer::class)
        ->args([
            service(InventoryContextFactory::class),
            service('swag.paypal.pos.sync.inventory.local_updater'),
            service(RemoteUpdater::class),
            service('swag_paypal_pos_sales_channel_inventory.repository'),
        ]);

    $services->set(StockSubscriber::class)
        ->args([
            service('messenger.default_bus'),
            service('sales_channel.repository'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ProductContextFactory::class)
        ->args([
            service('swag_paypal_pos_sales_channel_product.repository'),
            service('swag_paypal_pos_sales_channel_media.repository'),
        ]);

    $services->set(NewUpdater::class)
        ->args([
            service(ProductResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(OutdatedUpdater::class)
        ->args([
            service(ProductResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(DeletedUpdater::class)
        ->args([
            service(ProductResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
            service(UuidConverter::class),
        ]);

    $services->set(UnsyncedChecker::class)
        ->args([
            service(ProductResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
            service(UuidConverter::class),
        ]);

    $services->set(ProductSyncer::class)
        ->args([
            service(ProductConverter::class),
            service(ProductContextFactory::class),
            service(NewUpdater::class),
            service(OutdatedUpdater::class),
            service(DeletedUpdater::class),
            service(UnsyncedChecker::class),
        ]);
};

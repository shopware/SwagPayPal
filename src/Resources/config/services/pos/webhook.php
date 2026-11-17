<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalWebhookCalculator;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Webhook\Handler\InventoryChangedHandler;
use Swag\PayPal\Pos\Webhook\WebhookController;
use Swag\PayPal\Pos\Webhook\WebhookRegistry;
use Swag\PayPal\Pos\Webhook\WebhookService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(WebhookController::class)
        ->public()
        ->args([
            service('monolog.logger.paypal'),
            service(WebhookService::class),
            service('sales_channel.repository'),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(WebhookService::class)
        ->public()
        ->args([
            service(SubscriptionResource::class),
            service(WebhookRegistry::class),
            service('sales_channel.repository'),
            service(SystemConfigService::class),
            service(UuidConverter::class),
            service('router'),
        ]);

    // handler registry
    $services->set(WebhookRegistry::class)
        ->args([
            tagged_iterator('swag.paypal.pos.webhook.handler'),
        ]);

    // handler
    $services->set(InventoryChangedHandler::class)
        ->args([
            service(ApiKeyDecoder::class),
            service(RunService::class),
            service(LocalWebhookCalculator::class),
            service('swag.paypal.pos.sync.inventory.local_webhook_updater'),
            service(InventorySyncer::class),
            service(InventoryContextFactory::class),
            service('product.repository'),
            service(UuidConverter::class),
            param('shopware.stock.enable_stock_management'),
        ])
        ->tag('swag.paypal.pos.webhook.handler');
};

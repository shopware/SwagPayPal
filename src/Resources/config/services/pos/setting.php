<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Pos\Resource\UserResource;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Setting\Service\ApiCredentialService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Service\InformationFetchService;
use Swag\PayPal\Pos\Setting\Service\ProductCountService;
use Swag\PayPal\Pos\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\Pos\Setting\SettingsController;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(SettingsController::class)
        ->public()
        ->args([
            service(ApiCredentialService::class),
            service(InformationFetchService::class),
            service(InformationDefaultService::class),
            service(ProductVisibilityCloneService::class),
            service(ProductCountService::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ApiCredentialService::class)
        ->args([
            service(TokenResource::class),
            service('sales_channel.repository'),
            service(ApiKeyDecoder::class),
        ]);

    $services->set(InformationFetchService::class)
        ->args([
            service(UserResource::class),
            service('country.repository'),
            service('currency.repository'),
            service('language.repository'),
        ]);

    $services->set(ProductCountService::class)
        ->args([
            service(ProductResource::class),
            service(ProductSelection::class),
            service('sales_channel.product.repository'),
            service('sales_channel.repository'),
        ]);

    $services->set(InformationDefaultService::class)
        ->public()
        ->args([
            service('customer_group.repository'),
            service('category.repository'),
            service(PluginIdProvider::class),
            service('payment_method.repository'),
            service('rule.repository'),
            service('delivery_time.repository'),
            service('shipping_method.repository'),
        ]);

    $services->set(ProductVisibilityCloneService::class)
        ->args([
            service(MessageDispatcher::class),
            service('product_visibility.repository'),
            service(RunService::class),
            service('sales_channel.repository'),
        ]);
};

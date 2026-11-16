<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Contract\RequestServiceInterface;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Gateway\PaymentV1Gateway;
use Shopware\PayPalSDK\Gateway\TokenGateway;
use Shopware\PayPalSDK\Gateway\WebhookGateway;
use Swag\PayPal\RestApi\ApiContextFactory;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Client\Client;
use Swag\PayPal\RestApi\RequestService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // CACHE
    $services->set('paypal-sdk.token-cache', Psr16Cache::class)
        ->args([
            service('cache.object'),
        ]);

    // HTTP CLIENT
    $services->set('paypal-sdk.client', Client::class)
        ->args([
            service('monolog.logger.paypal'),
            service('psr18.paypal.base-client'),
        ]);

    // CONTEXT FACTORY
    $services->set(ApiContextFactoryInterface::class, ApiContextFactory::class)
        ->args([
            service(SettingsValidationService::class),
            service(SystemConfigService::class),
            service(CredentialsUtil::class),
        ]);

    $services->set(RequestServiceInterface::class, RequestService::class);

    // SDK GATEWAYS
    $services->set(TokenGatewayInterface::class, TokenGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service('paypal-sdk.token-cache'),
            service(RequestServiceInterface::class),
        ]);

    $services->set(OrderGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);

    $services->set(CustomerGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);

    $services->set(PaymentGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);

    $services->set(PaymentV1Gateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);

    $services->set(WebhookGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);
};

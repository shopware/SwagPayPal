<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Gateway\PaymentV1Gateway;
use Shopware\PayPalSDK\Gateway\WebhookGateway;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\V1\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V1\Resource\CaptureResource;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(AuthorizationResource::class)
        ->args([
            service(PaymentV1Gateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(CaptureResource::class)
        ->args([
            service(PaymentV1Gateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(MerchantIntegrationsResource::class)
        ->args([
            service(CustomerGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(DisputeResource::class)
        ->args([
            service(CustomerGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(OrdersResource::class)
        ->args([
            service(PaymentV1Gateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(PaymentResource::class)
        ->args([
            service(PaymentV1Gateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(SaleResource::class)
        ->args([
            service(PaymentV1Gateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(TokenResource::class)
        ->args([
            service(TokenGatewayInterface::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(WebhookResource::class)
        ->args([
            service(WebhookGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);
};

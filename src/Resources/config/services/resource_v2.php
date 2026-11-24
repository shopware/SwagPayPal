<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\V2\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V2\Resource\CaptureResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V2\Resource\RefundResource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(AuthorizationResource::class)
        ->args([
            service(PaymentGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(CaptureResource::class)
        ->args([
            service(PaymentGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);

    $services->set(OrderResource::class)
        ->args([
            service(OrderGateway::class),
            service(ApiContextFactoryInterface::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(RefundResource::class)
        ->args([
            service(PaymentGateway::class),
            service(ApiContextFactoryInterface::class),
        ]);
};

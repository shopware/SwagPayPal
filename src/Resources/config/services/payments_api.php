<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\PaymentsApi\Administration\PayPalPaymentController;
use Swag\PayPal\RestApi\V1\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V1\Resource\CaptureResource;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PayPalPaymentController::class)
        ->public()
        ->args([
            service(PaymentResource::class),
            service(SaleResource::class),
            service(AuthorizationResource::class),
            service(OrdersResource::class),
            service(CaptureResource::class),
            service('order.repository'),
        ])
        ->call('setContainer', [service('service_container')]);
};

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Psr\Clock\ClockInterface;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessageHandler;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationRetryStrategy;
use Swag\PayPal\Checkout\Order\Shipping\ShippingSubscriber;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(ShippingSubscriber::class)
        ->args([
            service('messenger.default_bus'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ShippingInformationMessageHandler::class)
        ->args([
            service('order_delivery.repository'),
            service(OrderResource::class),
            service('monolog.logger.paypal'),
        ])
        ->tag('messenger.message_handler');

    $services->set(ShippingInformationRetryStrategy::class)
        ->decorate('messenger.retry.multiplier_retry_strategy.async')
        ->args([
            service('.inner'),
            service(ClockInterface::class),
        ]);
};

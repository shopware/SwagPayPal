<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

return static function (ContainerBuilder $container): void {
    $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/services'));

    $loader->load('administration.xml');
    $loader->load('apm.xml');
    $loader->load('checkout.xml');
    $loader->load('client.xml');
    $loader->load('dal.xml');
    $loader->load('dev_ops.xml');
    $loader->load('dispute.xml');
    $loader->load('express_checkout.xml');
    $loader->load('installment.xml');
    $loader->load('orders_api.xml');
    $loader->load('payments_api.xml');
    $loader->load('plus.xml');
    $loader->load('pui.xml');
    $loader->load('reporting.xml');
    $loader->load('resource_v1.xml');
    $loader->load('resource_v2.xml');
    $loader->load('service_v1.xml');
    $loader->load('setting.xml');
    $loader->load('shipping.xml');
    $loader->load('storefront.xml');
    $loader->load('util.xml');
    $loader->load('webhook.xml');

    $loader->load('pos/api.xml');
    $loader->load('pos/command.xml');
    $loader->load('pos/dal.xml');
    $loader->load('pos/message_queue.xml');
    $loader->load('pos/run.xml');
    $loader->load('pos/schedule.xml');
    $loader->load('pos/setting.xml');
    $loader->load('pos/sync.xml');
    $loader->load('pos/webhook.xml');
};

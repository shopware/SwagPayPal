<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

return static function (ContainerBuilder $container): void {
    $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/services'));

    $loader->load('administration.php');
    $loader->load('agentic_commerce.php');
    $loader->load('apm.php');
    $loader->load('checkout.php');
    $loader->load('dal.php');
    $loader->load('dev_ops.php');
    $loader->load('dispute.php');
    $loader->load('document.php');
    $loader->load('express_checkout.php');
    $loader->load('installment.php');
    $loader->load('orders_api.php');
    $loader->load('payments_api.php');
    $loader->load('paypal_sdk.php');
    $loader->load('pui.php');
    $loader->load('reporting.php');
    $loader->load('resource_v1.php');
    $loader->load('resource_v2.php');
    $loader->load('setting.php');
    $loader->load('shipping.php');
    $loader->load('storefront.php');
    $loader->load('util.php');
    $loader->load('webhook.php');

    $loader->load('pos/api.php');
    $loader->load('pos/command.php');
    $loader->load('pos/dal.php');
    $loader->load('pos/message_queue.php');
    $loader->load('pos/run.php');
    $loader->load('pos/schedule.php');
    $loader->load('pos/setting.php');
    $loader->load('pos/sync.php');
    $loader->load('pos/webhook.php');
};

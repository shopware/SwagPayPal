<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('../../Administration/**/*Controller.php', 'attribute');

    if (\class_exists(Shopware\Core\Framework\JWT\Struct\JWKCollection::class)) {
        // Agentic commerce cannot authenticate agent requests without PayPal JWKS support (`Shopware\Core\Framework\JWT`),
        // which was only introduced after Shopware 6.6.0.0.
        $routes->import('../../AgenticCommerce/*Controller.php', 'attribute');
        $routes->import('../../AgenticCommerce/**/*Route.php', 'attribute');
    }

    $routes->import('../../Checkout/**/*Controller.php', 'attribute');
    $routes->import('../../Checkout/**/*Route.php', 'attribute');
    $routes->import('../../Dispute/**/*Controller.php', 'attribute');
    $routes->import('../../Pos/**/*Controller.php', 'attribute');
    $routes->import('../../OrdersApi/**/*Controller.php', 'attribute');
    $routes->import('../../PaymentsApi/**/*Controller.php', 'attribute');
    $routes->import('../../Setting/**/*Controller.php', 'attribute');
    $routes->import('../../Storefront/**/*Controller.php', 'attribute');
    $routes->import('../../Webhook/**/*Controller.php', 'attribute');
};

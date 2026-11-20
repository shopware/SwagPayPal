<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Checkout\Cart\SalesChannel\ShippingCostRoute;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Administration\PayPalOrdersController;
use Swag\PayPal\OrdersApi\Administration\Service\CaptureRefundCreator;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BancontactOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BlikOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BoletoBancarioOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\EpsOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\IdealOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MultibancoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MyBankOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\OxxoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\P24OrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\TrustlyOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PUIOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\CustomIdProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\V2\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V2\Resource\CaptureResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V2\Resource\RefundResource;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PayPalOrdersController::class)
        ->public()
        ->args([
            service(OrderResource::class),
            service(AuthorizationResource::class),
            service(CaptureResource::class),
            service(RefundResource::class),
            service('order_transaction.repository'),
            service(PaymentStatusUtilV2::class),
            service(CaptureRefundCreator::class),
            service('monolog.logger.paypal'),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(CaptureRefundCreator::class)
        ->args([
            service(PriceFormatter::class),
        ]);

    $services->set(AbstractOrderBuilder::class)
        ->abstract()
        ->args([
            service(SystemConfigService::class),
            service(PurchaseUnitProvider::class),
            service(AddressProvider::class),
            service(LocaleCodeProvider::class),
            service(ItemListProvider::class),
        ]);

    $services->set(PayPalOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class)
        ->args([
            service(VaultTokenService::class),
        ]);

    $services->set(ACDCOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class)
        ->args([
            service(VaultTokenService::class),
        ]);

    $services->set(VenmoOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class)
        ->args([
            service(VaultTokenService::class),
        ]);

    $services->set(ApplePayOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(GooglePayOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(PUIOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(BancontactOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(BlikOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(BoletoBancarioOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(EpsOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(IdealOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(MultibancoOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(MyBankOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(OxxoOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(P24OrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(TrustlyOrderBuilder::class)
        ->parent(AbstractOrderBuilder::class);

    $services->set(AddressProvider::class);

    $services->set(AmountProvider::class)
        ->args([
            service(PriceFormatter::class),
        ]);

    $services->set(CustomIdProvider::class)
        ->args([
            service('plugin.repository'),
            param('kernel.shopware_version'),
        ]);

    $services->set(ItemListProvider::class)
        ->args([
            service(PriceFormatter::class),
            service('event_dispatcher'),
            service('monolog.logger.paypal'),
        ]);

    $services->set(PurchaseUnitProvider::class)
        ->args([
            service(AmountProvider::class),
            service(AddressProvider::class),
            service(CustomIdProvider::class),
            service(SystemConfigService::class),
        ]);

    $services->set(ShippingOptionsProvider::class)
        ->args([
            service(PriceFormatter::class),
            service(ShippingMethodRoute::class),
            service(ShippingCostRoute::class)->nullOnInvalid(),
            param('kernel.shopware_version'),
        ]);

    $services->set(OrderNumberPatchBuilder::class);

    $services->set(PurchaseUnitPatchBuilder::class)
        ->args([
            service(PurchaseUnitProvider::class),
            service(ItemListProvider::class),
        ]);
};

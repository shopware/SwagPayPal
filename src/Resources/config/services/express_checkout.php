<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRoute;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Checkout\Shipping\BlockedShippingMethodSwitcher;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCategoryRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PayPalExpressCheckoutDataService::class)
        ->args([
            service(CartService::class),
            service(LocaleCodeProvider::class),
            service('router'),
            service(PaymentMethodUtil::class),
            service(SystemConfigService::class),
            service(CredentialsUtil::class),
            service(CartPriceService::class),
            service(PayLaterMethodData::class),
        ]);

    $services->set(ExpressPrepareCheckoutRoute::class)
        ->public()
        ->args([
            service(ExpressCustomerService::class),
            service(SalesChannelContextFactory::class),
            service(OrderResource::class),
            service(CartService::class),
            service(CartPriceService::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(ExpressCustomerService::class)
        ->args([
            service(RegisterRoute::class),
            service('sales_channel.country.repository'),
            service('country_state.repository'),
            service('salutation.repository'),
            service('customer.repository'),
            service(AccountService::class),
            service(SystemConfigService::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(ExpressCreateOrderRoute::class)
        ->public()
        ->args([
            service(CartService::class),
            service(PayPalOrderBuilder::class),
            service(OrderResource::class),
            service(CartPriceService::class),
            service(SystemConfigService::class),
            service('router'),
            service('monolog.logger.paypal'),
        ]);

    $services->set(ExpressShippingCallbackService::class)
        ->args([
            service(CartService::class),
            service('sales_channel.country.repository'),
            service(PayPalOrderBuilder::class),
            service(ShippingOptionsProvider::class),
            service(ContextSwitchRoute::class),
            service(SalesChannelContextFactory::class),
            service(BlockedShippingMethodSwitcher::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(ExpressShippingCallbackRoute::class)
        ->public()
        ->args([
            service(ExpressShippingCallbackService::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(ExpressCheckoutSubscriber::class)
        ->args([
            service(PayPalExpressCheckoutDataService::class),
            service(SettingsValidationService::class),
            service(SystemConfigService::class),
            service(PaymentMethodUtil::class),
            service(ExcludedProductValidator::class),
            service('customer.repository'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ExpressCategoryRoute::class)
        ->decorate(CategoryRoute::class, null, -2000)
        ->public()
        ->args([
            service(ExpressCategoryRoute::class . '.inner'),
            service(PayPalExpressCheckoutDataService::class),
            service(SettingsValidationService::class),
            service(SystemConfigService::class),
            service(PaymentMethodUtil::class),
        ]);
};

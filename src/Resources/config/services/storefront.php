<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Psr\Clock\ClockInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\Snippet\Files\SnippetFileLoader;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\SalesChannel\ClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\ClientTokenRoute;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityStateService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Storefront\Controller\ApplePayController;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Storefront\Data\CheckoutDataSubscriber;
use Swag\PayPal\Storefront\Data\FundingSubscriber;
use Swag\PayPal\Storefront\Data\Service\AbstractCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\ACDCCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\ApplePayCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
use Swag\PayPal\Storefront\Data\Service\GooglePayCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\PayLaterCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SEPACheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SPBCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\VaultDataService;
use Swag\PayPal\Storefront\Data\Service\VenmoCheckoutDataService;
use Swag\PayPal\Storefront\Data\VaultSubscriber;
use Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber;
use Swag\PayPal\Storefront\Framework\Cookie\GooglePayCookieProvider;
use Swag\PayPal\Storefront\Framework\Cookie\PayPalCookieProvider;
use Swag\PayPal\Storefront\Framework\Snippet\SnippetFileLoaderDecorator;
use Swag\PayPal\Storefront\RequestSubscriber;
use Swag\PayPal\Storefront\Service\ReturnTokenService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PayPalController::class)
        ->public()
        ->args([
            service(CreateOrderRoute::class),
            service(MethodEligibilityRoute::class),
            service(PUIPaymentInstructionsRoute::class),
            service(ExpressPrepareCheckoutRoute::class),
            service(ExpressCreateOrderRoute::class),
            service(ExpressShippingCallbackRoute::class),
            service(ContextSwitchRoute::class),
            service(CartDeleteRoute::class),
            service(CartService::class),
            service(CartPriceService::class),
            service(ClearVaultRoute::class),
            service(ClientTokenRoute::class),
            service('monolog.logger.paypal'),
            service(ReturnTokenService::class),
            service('router'),
            service(SystemConfigService::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ReturnTokenService::class)
        ->args([
            service('shopware.jwt_config'),
            service(ClockInterface::class),
            service(SystemConfigService::class),
        ]);

    $services->set(CookieProviderSubscriber::class)
        ->args([
            service(PaymentMethodUtil::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(PayPalCookieProvider::class)
        ->decorate(CookieProviderInterface::class)
        ->args([
            service(PayPalCookieProvider::class . '.inner'),
            service(PaymentMethodUtil::class),
            service('request_stack'),
        ]);

    $services->set(GooglePayCookieProvider::class)
        ->decorate(CookieProviderInterface::class)
        ->args([
            service(GooglePayCookieProvider::class . '.inner'),
            service(PaymentMethodUtil::class),
            service('request_stack'),
        ]);

    $services->set(RequestSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(FundingSubscriber::class)
        ->args([
            service(SettingsValidationService::class),
            service(FundingEligibilityDataService::class),
            service(PaymentMethodUtil::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(VaultSubscriber::class)
        ->args([
            service(SettingsValidationService::class),
            service(VaultDataService::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(FundingEligibilityDataService::class)
        ->args([
            service(CredentialsUtil::class),
            service(SystemConfigService::class),
            service(LocaleCodeProvider::class),
            service('router'),
            service('request_stack'),
            service(MethodEligibilityStateService::class),
        ]);

    $services->set(VaultDataService::class)
        ->args([
            service('swag_paypal_vault_token.repository'),
            service(PaymentMethodDataRegistry::class),
        ]);

    $services->set(AbstractCheckoutDataService::class)
        ->abstract()
        ->args([
            service(PaymentMethodDataRegistry::class),
            service(LocaleCodeProvider::class),
            service('router'),
            service(SystemConfigService::class),
            service(CredentialsUtil::class),
        ]);

    $services->set(ACDCCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public();

    $services->set(ApplePayCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public();

    $services->set(GooglePayCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public();

    $services->set(PayLaterCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public();

    $services->set(SEPACheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public();

    $services->set(SPBCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public()
        ->args([
            service(CustomerVaultTokenRoute::class),
        ]);

    $services->set(VenmoCheckoutDataService::class)
        ->parent(AbstractCheckoutDataService::class)
        ->public()
        ->args([
            service(CustomerVaultTokenRoute::class),
        ]);

    $services->set(CheckoutDataSubscriber::class)
        ->args([
            service(SettingsValidationService::class),
            service('request_stack'),
            service('event_dispatcher'),
            tagged_iterator('swag.paypal.payment.storefront.checkout.data'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ApplePayController::class)
        ->public()
        ->args([
            service(CredentialsUtil::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(SnippetFileLoaderDecorator::class)
        ->decorate(SnippetFileLoader::class)
        ->args([
            service('.inner'),
        ]);
};

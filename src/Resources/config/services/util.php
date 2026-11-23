<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Monolog\Processor\UidProcessor;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Availability\AvailabilityService;
use Swag\PayPal\Util\IntrospectionProcessor;
use Swag\PayPal\Util\Lifecycle\ActivateDeactivate;
use Swag\PayPal\Util\Lifecycle\Installer\MediaInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\ApplePayMethodData;
use Swag\PayPal\Util\Lifecycle\Method\BancontactMethodData;
use Swag\PayPal\Util\Lifecycle\Method\BlikMethodData;
use Swag\PayPal\Util\Lifecycle\Method\EpsMethodData;
use Swag\PayPal\Util\Lifecycle\Method\GooglePayMethodData;
use Swag\PayPal\Util\Lifecycle\Method\IdealMethodData;
use Swag\PayPal\Util\Lifecycle\Method\MultibancoMethodData;
use Swag\PayPal\Util\Lifecycle\Method\MyBankMethodData;
use Swag\PayPal\Util\Lifecycle\Method\OxxoMethodData;
use Swag\PayPal\Util\Lifecycle\Method\P24MethodData;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;
use Swag\PayPal\Util\Lifecycle\Method\TrustlyMethodData;
use Swag\PayPal\Util\Lifecycle\Method\VenmoMethodData;
use Swag\PayPal\Util\Lifecycle\PluginLifecycleSubscriber;
use Swag\PayPal\Util\Lifecycle\State\AgenticCommerceService;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\Log\PaymentStatusChangeSubscriber;
use Swag\PayPal\Util\PaymentMethodUtil;
use Swag\PayPal\Util\PaymentStatusUtil;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PaymentMethodUtil::class)
        ->args([
            service(Connection::class),
            service('sales_channel.repository'),
            service(PaymentMethodDataRegistry::class),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(PaymentStatusUtil::class)
        ->args([
            service('order.repository'),
            service(OrderTransactionStateHandler::class),
            service(PriceFormatter::class),
        ]);

    $services->set(PaymentStatusUtilV2::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(PriceFormatter::class),
        ]);

    $services->set(PriceFormatter::class);

    $services->set(LocaleCodeProvider::class)
        ->args([
            service('language.repository'),
            service('monolog.logger.paypal'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('monolog.processor.paypal.introspection', IntrospectionProcessor::class)
        ->arg('$requestStack', service('request_stack'))
        ->tag('monolog.processor', ['channel' => 'paypal']);

    $services->set('monolog.processor.paypal.uid', UidProcessor::class)
        ->tag('monolog.processor', ['channel' => 'paypal'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(PaymentStatusChangeSubscriber::class)
        ->args([
            service('monolog.logger.paypal'),
            service('order_transaction.repository'),
            service(PaymentMethodDataRegistry::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ActivateDeactivate::class)
        ->args([
            service(PaymentMethodStateService::class),
            service(PosStateService::class),
            service(AgenticCommerceService::class),
        ]);

    $services->set(PluginLifecycleSubscriber::class)
        ->args([
            service(SwagPayPal::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(PaymentMethodStateService::class)
        ->public()
        ->args([
            service(PaymentMethodDataRegistry::class),
            service('payment_method.repository'),
        ]);

    $services->set(PosStateService::class)
        ->args([
            service('sales_channel.repository'),
            service('sales_channel_type.repository'),
            service('shipping_method.repository'),
            service('payment_method.repository'),
        ]);

    $services->set(AgenticCommerceService::class)
        ->args([
            service('sales_channel.repository'),
            service('sales_channel_type.repository'),
        ]);

    $services->set(PaymentMethodInstaller::class)
        ->public()
        ->args([
            service('payment_method.repository'),
            service('rule.repository'),
            service(PluginIdProvider::class),
            service(PaymentMethodDataRegistry::class),
            service(MediaInstaller::class),
        ]);

    $services->set(MediaInstaller::class)
        ->public()
        ->args([
            service('media.repository'),
            service('media_folder.repository'),
            service('payment_method.repository'),
            service(FileSaver::class),
        ]);

    $services->set(PaymentMethodDataRegistry::class)
        ->args([
            service('payment_method.repository'),
            service('service_container'),
            tagged_iterator('swag.paypal.payment.method.data'),
        ]);

    $services->set(AbstractMethodData::class)
        ->abstract()
        ->args([
            service('service_container'),
        ]);

    $services->set(PayPalMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(PUIMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(ACDCMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(SEPAMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(BancontactMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(BlikMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    // $services->set(\Swag\PayPal\Util\Lifecycle\Method\BoletoBancarioMethodData::class)
    //     ->parent(AbstractMethodData::class)
    //     ->tag('swag.paypal.payment.method.data');

    $services->set(EpsMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(IdealMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(MultibancoMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(MyBankMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(OxxoMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(P24MethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(TrustlyMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data');

    $services->set(VenmoMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(PayLaterMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(ApplePayMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(GooglePayMethodData::class)
        ->parent(AbstractMethodData::class)
        ->tag('swag.paypal.payment.method.data')
        ->tag('swag.paypal.payment.storefront.checkout.data');

    $services->set(AvailabilityService::class)
        ->args([
            service(PaymentMethodDataRegistry::class),
        ]);
};

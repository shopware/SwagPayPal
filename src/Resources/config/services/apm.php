<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\APMHandler;
use Swag\PayPal\OrdersApi\Builder\APM\BancontactOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BlikOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\EpsOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\IdealOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MultibancoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MyBankOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\OxxoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\P24OrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\TrustlyOrderBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('Swag\PayPal\Checkout\Payment\Method\BancontactAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(BancontactOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\BlikAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(BlikOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    // $services->set('Swag\PayPal\Checkout\Payment\Method\BoletoBancarioAPMHandler', APMHandler::class)
    //     ->parent(APMHandler::class)
    //     ->args([
    //         service(\Swag\PayPal\OrdersApi\Builder\APM\BoletoBancarioOrderBuilder::class),
    //     ])
    //     ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\EpsAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(EpsOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\IdealAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(IdealOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\MultibancoAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(MultibancoOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\MyBankAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(MyBankOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\OxxoAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(OxxoOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\P24APMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(P24OrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set('Swag\PayPal\Checkout\Payment\Method\TrustlyAPMHandler', APMHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(TrustlyOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');
};

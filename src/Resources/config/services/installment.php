<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Installment\Banner\InstallmentBannerSubscriber;
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(InstallmentBannerSubscriber::class)
        ->args([
            service(SettingsValidationService::class),
            service(PaymentMethodUtil::class),
            service(BannerDataService::class),
            service(ExcludedProductValidator::class),
            service('monolog.logger.paypal'),
            service(PayLaterMethodData::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(BannerDataService::class)
        ->args([
            service(LocaleCodeProvider::class),
            service(SystemConfigService::class),
            service(CredentialsUtil::class),
            service('router'),
            service(PaymentMethodUtil::class),
            service('language.repository'),
        ]);
};

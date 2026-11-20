<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\Service\SdkV6EligibilityService;
use Swag\PayPal\Setting\Service\SettingsSaver;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\SettingsController;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(SettingsController::class)
        ->public()
        ->args([
            service(ApiCredentialService::class),
            service(MerchantIntegrationsService::class),
            service(SystemConfigValidator::class),
            service(SettingsSaver::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ApiCredentialService::class)
        ->args([
            service(TokenGatewayInterface::class),
            service(CustomerGateway::class),
        ]);

    $services->set(CredentialsUtil::class)
        ->args([
            service(SystemConfigService::class),
        ]);

    $services->set(MerchantIntegrationsService::class)
        ->args([
            service(MerchantIntegrationsResource::class),
            service(TokenResource::class),
            service(CredentialsUtil::class),
            service(PaymentMethodDataRegistry::class),
            service(SdkV6EligibilityService::class),
        ]);

    $services->set(SdkV6EligibilityService::class)
        ->args([
            service(TokenGatewayInterface::class),
            service(ApiContextFactoryInterface::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(SettingsValidationService::class)
        ->args([
            service(SystemConfigService::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(SettingsSaver::class)
        ->args([
            service(SystemConfigService::class),
            service(ApiCredentialService::class),
            service(WebhookSystemConfigHelper::class),
            service(SdkV6EligibilityService::class),
            service('monolog.logger.paypal'),
        ]);
};

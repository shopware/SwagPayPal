<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Handler\AuthorizationVoided;
use Swag\PayPal\Webhook\Handler\CaptureCompleted;
use Swag\PayPal\Webhook\Handler\CaptureDenied;
use Swag\PayPal\Webhook\Handler\CaptureRefunded;
use Swag\PayPal\Webhook\Handler\CaptureReversed;
use Swag\PayPal\Webhook\Handler\SaleComplete;
use Swag\PayPal\Webhook\Handler\SaleDenied;
use Swag\PayPal\Webhook\Handler\SaleRefunded;
use Swag\PayPal\Webhook\Handler\VaultPaymentTokenCreated;
use Swag\PayPal\Webhook\Handler\VaultPaymentTokenDeleted;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookSubscriber;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookRegistry;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(WebhookController::class)
        ->public()
        ->args([
            service('monolog.logger.paypal'),
            service(WebhookService::class),
            service('system_config.repository'),
            service(LastWebhookStorage::class),
            service(WebhookUrlBuilder::class),
            service('request_stack'),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(WebhookService::class)
        ->public()
        ->args([
            service(WebhookResource::class),
            service(WebhookRegistry::class),
            service(SystemConfigService::class),
            service(WebhookRegistrationChecker::class),
            service(WebhookUrlBuilder::class),
            service('monolog.logger.paypal'),
        ]);

    // handler registry
    $services->set(WebhookRegistry::class)
        ->args([
            tagged_iterator('swag.paypal.webhook.handler'),
        ]);

    // handler
    $services->set(AuthorizationVoided::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(CaptureCompleted::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(PaymentStatusUtilV2::class),
            service(PaymentMethodDataRegistry::class),
            service(PUIInstructionsFetchService::class),
            service(TransactionDataService::class),
            service(OrderResource::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(CaptureDenied::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(CaptureRefunded::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(PaymentStatusUtilV2::class),
            service(OrderResource::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(CaptureReversed::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(PaymentStatusUtilV2::class),
            service(OrderResource::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(SaleComplete::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(SaleDenied::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(SaleRefunded::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(SaleResource::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(VaultPaymentTokenCreated::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(VaultTokenService::class),
            service(PaymentTransactionStructFactory::class),
            service(OrderResource::class),
        ])
        ->tag('swag.paypal.webhook.handler');

    $services->set(VaultPaymentTokenDeleted::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service('swag_paypal_vault_token.repository'),
        ])
        ->tag('swag.paypal.webhook.handler');

    // registration
    $services->set(WebhookUrlBuilder::class)
        ->args([
            service('router'),
            service('request_stack'),
            env('APP_URL')->default('')->string(),
        ]);

    $services->set(WebhookRegistrationChecker::class)
        ->args([
            service(WebhookResource::class),
            service(SystemConfigService::class),
            service(WebhookUrlBuilder::class),
        ]);

    $services->set(WebhookSystemConfigHelper::class)
        ->args([
            service('monolog.logger.paypal'),
            service(WebhookService::class),
            service(SystemConfigService::class),
            service(SettingsValidationService::class),
        ]);

    $services->set(WebhookSubscriber::class)
        ->args([
            service('monolog.logger.paypal'),
            service(SystemConfigService::class),
            service(WebhookService::class),
            service(WebhookSystemConfigHelper::class),
            service('request_stack'),
        ])
        ->tag('kernel.event_subscriber');
};

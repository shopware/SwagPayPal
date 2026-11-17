<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\SalesChannel\SetPaymentOrderRoute;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRoute;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Card\AbstractCardValidator;
use Swag\PayPal\Checkout\Card\ACDCValidator;
use Swag\PayPal\Checkout\Card\ApplePayValidator;
use Swag\PayPal\Checkout\Card\GooglePayValidator;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\Cart\Validation\CartValidator;
use Swag\PayPal\Checkout\CheckoutSubscriber;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessageHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\ApplePayHandler;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\ScheduledTask\OrderTransactionsCleanupTask;
use Swag\PayPal\Checkout\Payment\ScheduledTask\OrderTransactionsCleanupTaskHandler;
use Swag\PayPal\Checkout\Payment\ScheduledTask\TransactionStatusSyncTask;
use Swag\PayPal\Checkout\Payment\ScheduledTask\TransactionStatusSyncTaskHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\ClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\ClientTokenRoute;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\Checkout\SalesChannel\FilteredPaymentMethodRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityStateService;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Util\Availability\AvailabilityService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CheckoutSubscriber::class)
        ->args([
            service(PaymentMethodDataRegistry::class),
            service('monolog.logger.paypal'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ExcludedProductValidator::class)
        ->args([
            service(SystemConfigService::class),
            service('sales_channel.product.repository'),
        ]);

    $services->set(CartValidator::class)
        ->args([
            service(CartPriceService::class),
            service(PaymentMethodDataRegistry::class),
            service(SettingsValidationService::class),
            service('request_stack'),
            service(ExcludedProductValidator::class),
            service(AvailabilityService::class),
            service(MethodEligibilityStateService::class),
        ])
        ->tag('shopware.cart.validator')
        ->tag('subscription.cart.validator');

    $services->set(CartPriceService::class)
        ->args([
            service(PriceFormatter::class),
        ]);

    $services->set(MethodEligibilityStateService::class)
        ->args([
            service(SalesChannelContextPersister::class),
        ]);

    $services->set(MethodEligibilityRoute::class)
        ->public()
        ->args([
            service('monolog.logger.paypal'),
            service(MethodEligibilityStateService::class),
        ]);

    $services->set(CreateOrderRoute::class)
        ->public()
        ->args([
            service(CartService::class),
            service('order.repository'),
            service(PayPalOrderBuilder::class),
            service(ACDCOrderBuilder::class),
            service(ApplePayOrderBuilder::class),
            service(GooglePayOrderBuilder::class),
            service(VenmoOrderBuilder::class),
            service(OrderResource::class),
            service('monolog.logger.paypal'),
            service(PaymentTransactionStructFactory::class),
            service(SetPaymentOrderRoute::class),
        ]);

    $services->set(CustomerVaultTokenRoute::class)
        ->public()
        ->args([
            service('swag_paypal_vault_token.repository'),
            service(TokenResource::class),
        ]);

    $services->set(ClientTokenRoute::class)
        ->public()
        ->args([
            service(TokenResource::class),
            service('swag_paypal_vault_token.repository'),
            service(PaymentMethodDataRegistry::class),
        ]);

    $services->set(FilteredPaymentMethodRoute::class)
        ->decorate(PaymentMethodRoute::class, null, -1500)
        ->public()
        ->args([
            service('.inner'),
            service(PaymentMethodDataRegistry::class),
            service(SettingsValidationService::class),
            service(CartService::class),
            service(CartPriceService::class),
            service(ExcludedProductValidator::class),
            service('request_stack'),
            service(AvailabilityService::class),
            service('order.repository'),
            service(MethodEligibilityStateService::class),
        ]);

    $services->set(AbstractCardValidator::class)
        ->args([
            service(SystemConfigService::class),
        ]);

    $services->set(ApplePayValidator::class)
        ->parent(AbstractCardValidator::class);

    $services->set(GooglePayValidator::class)
        ->parent(AbstractCardValidator::class);

    $services->set(ACDCValidator::class)
        ->parent(AbstractCardValidator::class);

    $services->set(AbstractPaymentMethodHandler::class)
        ->abstract()
        ->args([
            service(SettingsValidationService::class),
            service(StateMachineRegistry::class),
            service(OrderExecuteService::class),
            service(OrderPatchService::class),
            service(TransactionDataService::class),
            service(OrderResource::class),
            service(VaultTokenService::class),
            service('order_transaction.repository'),
        ])
        ->call('setLogger', [service('monolog.logger.paypal')]);

    $services->set(ACDCHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(ACDCOrderBuilder::class),
            service(ACDCValidator::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(PayLaterHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(PayPalOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(SEPAHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(PayPalOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(VenmoHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(VenmoOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(ApplePayHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(ApplePayOrderBuilder::class),
            service(ApplePayValidator::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(GooglePayHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(GooglePayOrderBuilder::class),
            service(GooglePayValidator::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(PayPalPaymentHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(PayPalOrderBuilder::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(OrderExecuteService::class)
        ->args([
            service(OrderResource::class),
            service(OrderTransactionStateHandler::class),
            service(OrderNumberPatchBuilder::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(OrderPatchService::class)
        ->args([
            service(SystemConfigService::class),
            service(PurchaseUnitPatchBuilder::class),
            service(OrderResource::class),
        ]);

    $services->set(TransactionDataService::class)
        ->args([
            service('order_transaction.repository'),
            service(CredentialsUtil::class),
            service(OrderTransactionService::class),
        ]);

    $services->set(OrderTransactionService::class)
        ->args([
            service(Connection::class),
            service(ClockInterface::class),
        ]);

    $services->set(VaultTokenService::class)
        ->args([
            service('swag_paypal_vault_token.repository'),
            service('customer.repository'),
            service('subscription.repository')->nullOnInvalid(),
        ]);

    $services->set(ClearVaultRoute::class)
        ->public()
        ->args([
            service('swag_paypal_vault_token_mapping.repository'),
        ]);

    $services->set(TransactionStatusSyncTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(OrderTransactionsCleanupTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(OrderTransactionsCleanupTaskHandler::class)
        ->args([
            service('scheduled_task.repository'),
            service('logger'),
            service(Connection::class),
            service(ClockInterface::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(TransactionStatusSyncTaskHandler::class)
        ->args([
            service('scheduled_task.repository'),
            service('logger'),
            service('order_transaction.repository'),
            service(PaymentMethodDataRegistry::class),
            service('messenger.default_bus'),
            service(ClockInterface::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(TransactionStatusSyncMessageHandler::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderTransactionStateHandler::class),
            service(OrderResource::class),
            service(TransactionDataService::class),
            service('monolog.logger.paypal'),
        ])
        ->tag('messenger.message_handler');
};

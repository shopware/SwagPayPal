<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Psr\Clock\ClockInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\MessageQueue\PUIInstructionsFetchMessageHandler;
use Swag\PayPal\Checkout\PUI\PUISubscriber;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\ScheduledTask\PUIInstructionsFetchTask;
use Swag\PayPal\Checkout\PUI\ScheduledTask\PUIInstructionsFetchTaskHandler;
use Swag\PayPal\Checkout\PUI\Service\PUICustomerDataService;
use Swag\PayPal\Checkout\PUI\Service\PUIFraudNetDataService;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Swag\PayPal\Checkout\PUI\Service\PUIPaymentInstructionDataService;
use Swag\PayPal\OrdersApi\Builder\PUIOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PUIHandler::class)
        ->parent(AbstractPaymentMethodHandler::class)
        ->args([
            service(PUIOrderBuilder::class),
            service(PUICustomerDataService::class),
        ])
        ->tag('shopware.payment.method');

    $services->set(PUICustomerDataService::class)
        ->args([
            service('order.repository'),
            service('order_address.repository'),
            service('customer.repository'),
        ]);

    $services->set(PUIFraudNetDataService::class)
        ->args([
            service(PaymentMethodDataRegistry::class),
            service(CredentialsUtil::class),
        ]);

    $services->set(PUIPaymentInstructionsRoute::class)
        ->public()
        ->args([
            service('order_transaction.repository'),
            service(PUIInstructionsFetchService::class),
        ]);

    $services->set(PUIPaymentInstructionDataService::class)
        ->args([
            service(PaymentMethodDataRegistry::class),
            service(PUIPaymentInstructionsRoute::class),
            service('router'),
        ]);

    $services->set(PUIInstructionsFetchService::class)
        ->args([
            service('order_transaction.repository'),
            service(OrderResource::class),
            service(OrderTransactionStateHandler::class),
            service(TransactionDataService::class),
        ]);

    $services->set(PUISubscriber::class)
        ->args([
            service(SettingsValidationService::class),
            service(PUIFraudNetDataService::class),
            service(PUIPaymentInstructionDataService::class),
            service('monolog.logger.paypal'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(PUIInstructionsFetchTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(PUIInstructionsFetchTaskHandler::class)
        ->args([
            service('scheduled_task.repository'),
            service('logger'),
            service('order_transaction.repository'),
            service(PaymentMethodDataRegistry::class),
            service('messenger.default_bus'),
            service(ClockInterface::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(PUIInstructionsFetchMessageHandler::class)
        ->args([
            service('order_transaction.repository'),
            service(PUIInstructionsFetchService::class),
        ])
        ->tag('messenger.message_handler');
};

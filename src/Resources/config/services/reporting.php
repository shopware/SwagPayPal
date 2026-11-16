<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Swag\PayPal\Reporting\DataAbstractionLayer\TransactionReport\TransactionReportDefinition;
use Swag\PayPal\Reporting\ScheduledTask\TurnoverReportingTask;
use Swag\PayPal\Reporting\ScheduledTask\TurnoverReportingTaskHandler;
use Swag\PayPal\Reporting\Subscriber\OrderTransactionSubscriber;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(TransactionReportDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_paypal_vault_token']);

    $services->set(TurnoverReportingTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(TurnoverReportingTaskHandler::class)
        ->args([
            service('scheduled_task.repository'),
            service('monolog.logger.paypal'),
            service(Connection::class),
            service('swag_paypal_transaction_report.repository'),
            param('kernel.shopware_version'),
            param('instance_id'),
            service(ClockInterface::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(OrderTransactionSubscriber::class)
        ->args([
            service(PaymentMethodDataRegistry::class),
            service('swag_paypal_transaction_report.repository'),
            service('order_transaction.repository'),
        ])
        ->tag('kernel.event_subscriber');
};

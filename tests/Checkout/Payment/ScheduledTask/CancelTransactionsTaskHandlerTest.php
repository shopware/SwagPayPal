<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\ScheduledTask\CancelTransactionsTaskHandler;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;

class CancelTransactionsTaskHandlerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderFixture;
    use OrderTransactionTrait;
    use StateMachineStateTrait;

    public function testRun(): void
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($context, $container, OrderTransactionStates::STATE_IN_PROGRESS);

        $twoDaysAgo = new \DateTime('now -2 days');
        $twoDaysAgo = $twoDaysAgo->setTimezone(new \DateTimeZone('UTC'));
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $connection->update(
            'order_transaction',
            ['created_at' => $twoDaysAgo->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            ['id' => Uuid::fromHexToBytes($transactionId)]
        );

        /** @var CancelTransactionsTaskHandler $handler */
        $handler = $container->get(CancelTransactionsTaskHandler::class);
        $handler->run();

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);

        $cancelledStateId = $this->getOrderTransactionStateIdByTechnicalName(
            OrderTransactionStates::STATE_CANCELLED,
            $container,
            $context
        );

        static::assertSame($cancelledStateId, $transaction->getStateId());
    }

    public function testRunDoesNotChangeOlderThanSevenDays(): void
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($context, $container, OrderTransactionStates::STATE_IN_PROGRESS);

        $tenDaysAgo = new \DateTime('now -10 days');
        $tenDaysAgo = $tenDaysAgo->setTimezone(new \DateTimeZone('UTC'));
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $connection->update(
            'order_transaction',
            ['created_at' => $tenDaysAgo->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            ['id' => Uuid::fromHexToBytes($transactionId)]
        );

        /** @var CancelTransactionsTaskHandler $handler */
        $handler = $container->get(CancelTransactionsTaskHandler::class);
        $handler->run();

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);

        $cancelledStateId = $this->getOrderTransactionStateIdByTechnicalName(
            OrderTransactionStates::STATE_IN_PROGRESS,
            $container,
            $context
        );

        static::assertSame($cancelledStateId, $transaction->getStateId());
    }

    public function testRunDoesNotCancelOtherStates(): void
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($context, $container, PayPalPaymentHandler::ORDER_TRANSACTION_STATE_AUTHORIZED);

        $twoDaysAgo = new \DateTime('now -2 days');
        $twoDaysAgo = $twoDaysAgo->setTimezone(new \DateTimeZone('UTC'));
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $connection->update(
            'order_transaction',
            ['created_at' => $twoDaysAgo->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            ['id' => Uuid::fromHexToBytes($transactionId)]
        );

        /** @var CancelTransactionsTaskHandler $handler */
        $handler = $container->get(CancelTransactionsTaskHandler::class);
        $handler->run();

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);

        $cancelledStateId = $this->getOrderTransactionStateIdByTechnicalName(
            PayPalPaymentHandler::ORDER_TRANSACTION_STATE_AUTHORIZED,
            $container,
            $context
        );

        static::assertSame($cancelledStateId, $transaction->getStateId());
    }
}

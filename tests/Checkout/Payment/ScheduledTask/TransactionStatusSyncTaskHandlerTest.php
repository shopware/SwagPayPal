<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\Checkout\Payment\ScheduledTask\TransactionStatusSyncTaskHandler;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionStatusSyncTaskHandler::class)]
class TransactionStatusSyncTaskHandlerTest extends TestCase
{
    private EntityRepository&MockObject $orderTransactionRepository;

    private PaymentMethodDataRegistry&MockObject $paymentMethodDataRegistry;

    private MessageBusInterface&MockObject $bus;

    private TransactionStatusSyncTaskHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $this->bus = $this->createMock(MessageBusInterface::class);

        $this->handler = new TransactionStatusSyncTaskHandler(
            $this->createMock(EntityRepository::class),
            $this->orderTransactionRepository,
            $this->paymentMethodDataRegistry,
            $this->bus,
        );
    }

    public function testRun(): void
    {
        $this->paymentMethodDataRegistry
            ->expects(static::once())
            ->method('getPaymentHandlers')
            ->willReturn([]);

        $order = (new OrderEntity())->assign(['salesChannelId' => 'sales-channel-id']);

        $firstTransaction = (new OrderTransactionEntity())->assign([
            'id' => 'first-transaction-id',
            'order' => $order,
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'first-paypal-order'],
        ]);

        $secondTransaction = (new OrderTransactionEntity())->assign([
            'id' => 'second-transaction-id',
            'order' => $order,
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'second-paypal-order'],
        ]);

        $collection = new OrderTransactionCollection([$firstTransaction, $secondTransaction]);

        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(fn(Criteria $criteria, Context $context) => new EntitySearchResult(
                'order_transaction',
                $collection->count(),
                $collection,
                null,
                $criteria,
                $context,
            ));

        $matcher = static::exactly(2);
        $this->bus
            ->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(function (TransactionStatusSyncMessage $message) use ($matcher) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        static::assertSame('first-transaction-id', $message->getTransactionId());
                        static::assertSame('first-paypal-order', $message->getPayPalOrderId());

                        break;
                    case 2:
                        static::assertSame('second-transaction-id', $message->getTransactionId());
                        static::assertSame('second-paypal-order', $message->getPayPalOrderId());

                        break;
                }

                return new Envelope($message);
            });

        $this->handler->run();
    }

    public function testRunWithMalformedPayPalOrderId(): void
    {
        $this->paymentMethodDataRegistry
            ->expects(static::once())
            ->method('getPaymentHandlers')
            ->willReturn([]);

        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'first-transaction-id',
            'order' => (new OrderEntity())->assign(['salesChannelId' => 'sales-channel-id']),
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 1234],
        ]);

        $collection = new OrderTransactionCollection([$transaction]);

        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(fn(Criteria $criteria, Context $context) => new EntitySearchResult(
                'order_transaction',
                $collection->count(),
                $collection,
                null,
                $criteria,
                $context,
            ));

        $this->bus
            ->expects(static::once())
            ->method('dispatch')
            ->willReturnCallback(function (TransactionStatusSyncMessage $message) {
                static::assertNull($message->getPayPalOrderId());

                return new Envelope($message);
            });

        $this->handler->run();
    }
}

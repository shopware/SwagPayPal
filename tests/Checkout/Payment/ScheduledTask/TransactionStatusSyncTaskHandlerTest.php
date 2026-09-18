<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\Checkout\Payment\ScheduledTask\TransactionStatusSyncTaskHandler;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionStatusSyncTaskHandler::class)]
class TransactionStatusSyncTaskHandlerTest extends TestCase
{
    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $orderTransactionRepository;

    private PaymentMethodDataRegistry&Stub $paymentMethodDataRegistry;

    private MessageBusInterface&MockObject $bus;

    private TransactionStatusSyncTaskHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = new StaticEntityRepository([], new OrderTransactionDefinition());
        $this->paymentMethodDataRegistry = static::createStub(PaymentMethodDataRegistry::class);
        $this->paymentMethodDataRegistry->method('getPaymentHandlers')->willReturn(['paypal-handler']);
        $this->bus = $this->createMock(MessageBusInterface::class);

        $this->handler = new TransactionStatusSyncTaskHandler(
            new StaticEntityRepository([new ScheduledTaskCollection()]),
            new NullLogger(),
            $this->orderTransactionRepository,
            $this->paymentMethodDataRegistry,
            $this->bus,
            new MockClock('2026-09-19 12:00:00 UTC'),
        );
    }

    public function testRun(): void
    {
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
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$firstTransaction, $secondTransaction]));

        $this->bus->expects($this->exactly(2))->method('dispatch')
            ->willReturnCallback(static function (TransactionStatusSyncMessage $message): Envelope {
                static::assertSame('sales-channel-id', $message->getSalesChannelId());
                static::assertSame([
                    'first-transaction-id' => 'first-paypal-order',
                    'second-transaction-id' => 'second-paypal-order',
                ][$message->getTransactionId()], $message->getPayPalOrderId());

                return new Envelope($message);
            });

        $this->handler->run();
    }

    public function testRunRetriesOnlyMarkedCancellationsBeyondTheNormalTimeWindow(): void
    {
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'old-transaction-id',
            'createdAt' => new \DateTimeImmutable('2026-09-10 12:00:00 UTC'),
            'order' => (new OrderEntity())->assign(['salesChannelId' => 'sales-channel-id']),
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
            ],
        ]);
        $this->orderTransactionRepository->addSearch(static function (Criteria $criteria) use ($transaction): OrderTransactionCollection {
            static::assertCount(2, $criteria->getFilters());
            static::assertEquals(new EqualsAnyFilter('paymentMethod.handlerIdentifier', ['paypal-handler']), $criteria->getFilters()[0]);
            $selection = $criteria->getFilters()[1];
            static::assertInstanceOf(MultiFilter::class, $selection);
            static::assertSame(MultiFilter::CONNECTION_OR, $selection->getOperator());
            static::assertCount(2, $selection->getQueries());

            $normalSelection = $selection->getQueries()[0];
            static::assertInstanceOf(MultiFilter::class, $normalSelection);
            static::assertSame(MultiFilter::CONNECTION_AND, $normalSelection->getOperator());
            static::assertEquals([
                new EqualsAnyFilter('stateMachineState.technicalName', [
                    OrderTransactionStates::STATE_UNCONFIRMED,
                    OrderTransactionStates::STATE_AUTHORIZED,
                    OrderTransactionStates::STATE_IN_PROGRESS,
                ]),
                new RangeFilter('createdAt', [
                    RangeFilter::LTE => (new \DateTimeImmutable('2026-09-19 11:00:00 UTC'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    RangeFilter::GTE => (new \DateTimeImmutable('2026-09-17 11:00:00 UTC'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]),
            ], $normalSelection->getQueries());

            $pendingCancellationSelection = $selection->getQueries()[1];
            static::assertInstanceOf(MultiFilter::class, $pendingCancellationSelection);
            static::assertSame(MultiFilter::CONNECTION_AND, $pendingCancellationSelection->getOperator());
            static::assertEquals([
                new EqualsAnyFilter('stateMachineState.technicalName', [
                    OrderTransactionStates::STATE_OPEN,
                    OrderTransactionStates::STATE_UNCONFIRMED,
                    OrderTransactionStates::STATE_IN_PROGRESS,
                ]),
                new NotFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('customFields.' . SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED, null),
                ]),
            ], $pendingCancellationSelection->getQueries());

            return new OrderTransactionCollection([$transaction]);
        });
        $this->bus->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (TransactionStatusSyncMessage $message): Envelope {
                static::assertSame('old-transaction-id', $message->getTransactionId());
                static::assertSame('paypal-order-id', $message->getPayPalOrderId());

                return new Envelope($message);
            });

        $this->handler->run();
    }

    public function testRunWithMalformedPayPalOrderId(): void
    {
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'first-transaction-id',
            'order' => (new OrderEntity())->assign(['salesChannelId' => 'sales-channel-id']),
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 1234],
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->bus->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (TransactionStatusSyncMessage $message): Envelope {
                static::assertNull($message->getPayPalOrderId());

                return new Envelope($message);
            });

        $this->handler->run();
    }
}

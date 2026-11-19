<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\PaymentStatusChangeSubscriber;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentStatusChangeSubscriber::class)]
class PaymentStatusChangeSubscriberTest extends TestCase
{
    use PayPalLogTrait;

    /**
     * @var EntityRepository<OrderTransactionCollection>&MockObject
     */
    private EntityRepository&MockObject $repository;

    private PaymentMethodDataRegistry&MockObject $paymentMethodDataRegistry;

    private PaymentStatusChangeSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $this->subscriber = new PaymentStatusChangeSubscriber($this->createPayPalLogger(), $this->repository, $this->paymentMethodDataRegistry);
    }

    public function testSubscribesToTransitions(): void
    {
        static::assertArrayHasKey(StateMachineTransitionEvent::class, PaymentStatusChangeSubscriber::getSubscribedEvents());
    }

    public function testLogsTransitionOfPayPalPayment(): void
    {
        $paymentMethod = (new PaymentMethodEntity())->assign(['technicalName' => 'swag_paypal']);
        $this->repository->expects($this->once())->method('search')->willReturnCallback(
            static function (Criteria $criteria, Context $context) use ($paymentMethod): EntitySearchResult {
                static::assertSame(['id'], $criteria->getIds());
                static::assertTrue($criteria->hasAssociation('order'));
                static::assertTrue($criteria->hasAssociation('paymentMethod'));

                return new EntitySearchResult('order_transaction', 1, new OrderTransactionCollection([self::createTransaction($paymentMethod)]), null, $criteria, $context);
            }
        );
        $this->paymentMethodDataRegistry->expects($this->once())->method('isPayPalPaymentMethod')->with($paymentMethod)->willReturn(true);

        $this->subscriber->onTransition($this->createEvent('order_transaction'));

        $record = $this->assertPayPalLogged(
            LogCode::PAYMENT_STATUS_CHANGED,
            Level::Info,
            message: 'Changed the payment status of order {orderNumber} from {fromState} to {toState}',
        );
        static::assertSame([
            LogKey::ORDER_TRANSACTION_ID => 'id',
            LogKey::ORDER_ID => 'order-id',
            LogKey::ORDER_NUMBER => '10000',
            LogKey::SALES_CHANNEL_ID => 'sales-channel-id',
            LogKey::PAYMENT_METHOD => 'swag_paypal',
            LogKey::CODE => LogCode::PAYMENT_STATUS_CHANGED,
            LogKey::FROM_STATE => 'open',
            LogKey::TO_STATE => 'paid',
        ], $record->context);
    }

    public function testIgnoresOtherPaymentMethods(): void
    {
        $this->repository->method('search')->willReturnCallback(
            static fn (Criteria $criteria, Context $context) => new EntitySearchResult('order_transaction', 1, new OrderTransactionCollection([self::createTransaction(new PaymentMethodEntity())]), null, $criteria, $context)
        );
        $this->paymentMethodDataRegistry->method('isPayPalPaymentMethod')->willReturn(false);

        $this->subscriber->onTransition($this->createEvent('order_transaction'));

        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testIgnoresUnknownTransactions(): void
    {
        $this->repository->method('search')->willReturnCallback(
            static fn (Criteria $criteria, Context $context) => new EntitySearchResult('order_transaction', 0, new OrderTransactionCollection(), null, $criteria, $context)
        );
        $this->paymentMethodDataRegistry->expects($this->never())->method('isPayPalPaymentMethod');

        $this->subscriber->onTransition($this->createEvent('order_transaction'));

        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testIgnoresOtherEntities(): void
    {
        $this->repository->expects($this->never())->method('search');

        $this->subscriber->onTransition($this->createEvent('order'));

        static::assertSame([], $this->getPayPalLogRecords());
    }

    private static function createTransaction(PaymentMethodEntity $paymentMethod): OrderTransactionEntity
    {
        return (new OrderTransactionEntity())->assign([
            'id' => 'id',
            'order' => (new OrderEntity())->assign(['id' => 'order-id', 'orderNumber' => '10000', 'salesChannelId' => 'sales-channel-id']),
            'paymentMethod' => $paymentMethod,
        ]);
    }

    private function createEvent(string $entityName): StateMachineTransitionEvent
    {
        $from = new StateMachineStateEntity();
        $from->setTechnicalName('open');
        $to = new StateMachineStateEntity();
        $to->setTechnicalName('paid');

        return new StateMachineTransitionEvent($entityName, 'id', $from, $to, Context::createDefaultContext());
    }
}

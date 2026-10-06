<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Reporting\Subscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use Shopware\Core\System\StateMachine\Transition;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Reporting\Subscriber\OrderTransactionSubscriber;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

/**
 * @internal
 *
 * @covers \Swag\PayPal\Reporting\Subscriber\OrderTransactionSubscriber
 */
#[Package('checkout')]
class OrderTransactionSubscriberTest extends TestCase
{
    private PaymentMethodDataRegistry&MockObject $methodDataRegistry;

    private EntityRepository&MockObject $transactionReportRepository;

    private EntityRepository&MockObject $orderTransactionRepository;

    private OrderTransactionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->methodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $this->methodDataRegistry
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->transactionReportRepository = $this->createMock(EntityRepository::class);
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);

        $this->subscriber = new OrderTransactionSubscriber(
            $this->methodDataRegistry,
            $this->transactionReportRepository,
            $this->orderTransactionRepository,
        );
    }

    public function testSubscribesToTransactionStateChange(): void
    {
        static::assertSame(
            ['state_machine.order_transaction.state_changed' => 'onTransactionStateChange'],
            OrderTransactionSubscriber::getSubscribedEvents(),
        );
    }

    public function testOnTransactionStateChange(): void
    {
        $this->expectTransactionSearch('transaction-id', $this->createTransaction('transaction-id'));

        $this->transactionReportRepository
            ->expects(static::once())
            ->method('upsert')
            ->with([[
                'orderTransactionId' => 'transaction-id',
                'currencyIso' => 'EUR',
                'totalPrice' => 10,
            ]]);

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', Context::createDefaultContext()));
    }

    public function testOnTransactionStateChangeReadsAndWritesInSystemScope(): void
    {
        $this->expectTransactionSearch('transaction-id', $this->createTransaction('transaction-id'));

        // e.g. a restricted integration without privileges on swag_paypal_transaction_report
        $context = new Context(new AdminApiSource(null, 'integration-id'));

        $this->transactionReportRepository
            ->expects(static::once())
            ->method('upsert')
            ->with(static::anything(), static::callback(
                static fn (Context $context): bool => $context->getScope() === Context::SYSTEM_SCOPE
            ));

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', $context));

        static::assertSame(Context::USER_SCOPE, $context->getScope());
    }

    public function testOnTransactionStateChangeWithoutPayPalPaymentHandler(): void
    {
        $this->expectTransactionSearch('transaction-id', $this->createTransaction('transaction-id', PrePayment::class));

        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', Context::createDefaultContext()));
    }

    public function testOnTransactionStateChangeWithSandboxTransaction(): void
    {
        $transaction = $this->createTransaction('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => true]);

        $this->expectTransactionSearch('transaction-id', $transaction);

        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', Context::createDefaultContext()));
    }

    public function testOnTransactionStateChangeWithoutTransaction(): void
    {
        $this->expectTransactionSearch('transaction-id', null);

        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', Context::createDefaultContext()));
    }

    public function testOnTransactionStateChangeIgnoresLeaveSide(): void
    {
        $event = $this->createEvent(
            'transaction-id',
            Context::createDefaultContext(),
            StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_LEAVE,
        );

        $this->orderTransactionRepository->expects(static::never())->method(static::anything());
        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($event);
    }

    public function testOnTransactionStateChangeIgnoresOtherStates(): void
    {
        $event = $this->createEvent(
            'transaction-id',
            Context::createDefaultContext(),
            nextState: OrderTransactionStates::STATE_CANCELLED,
        );

        $this->orderTransactionRepository->expects(static::never())->method(static::anything());
        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($event);
    }

    public function testOnTransactionStateChangeWithNonLiveVersion(): void
    {
        $context = new Context(
            new SystemSource(),
            versionId: 'random-non-live-version-id',
        );

        $this->orderTransactionRepository->expects(static::never())->method(static::anything());
        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onTransactionStateChange($this->createEvent('transaction-id', $context));
    }

    private function expectTransactionSearch(string $transactionId, ?OrderTransactionEntity $transaction): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(static function (Criteria $criteria, Context $context) use ($transactionId, $transaction): EntitySearchResult {
                static::assertSame([$transactionId], $criteria->getIds());
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                $collection = new OrderTransactionCollection($transaction ? [$transaction] : []);

                return new EntitySearchResult(OrderTransactionDefinition::ENTITY_NAME, $collection->count(), $collection, null, $criteria, $context);
            });
    }

    private function createEvent(
        string $transactionId,
        Context $context,
        string $side = StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER,
        string $nextState = OrderTransactionStates::STATE_PAID,
    ): StateMachineStateChangeEvent {
        return new StateMachineStateChangeEvent(
            $context,
            $side,
            new Transition(OrderTransactionDefinition::ENTITY_NAME, $transactionId, 'paid', 'stateId'),
            (new StateMachineEntity())->assign(['technicalName' => OrderTransactionStates::STATE_MACHINE]),
            (new StateMachineStateEntity())->assign(['technicalName' => OrderTransactionStates::STATE_OPEN]),
            (new StateMachineStateEntity())->assign(['technicalName' => $nextState]),
        );
    }

    private function createTransaction(string $id, string $handlerIdentifier = PayPalHandler::class): OrderTransactionEntity
    {
        return (new OrderTransactionEntity())->assign([
            'id' => $id,
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => $handlerIdentifier]),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'order' => (new OrderEntity())->assign([
                'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
            ]),
        ]);
    }
}

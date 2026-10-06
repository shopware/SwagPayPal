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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Reporting\Subscriber\OrderTransactionSubscriber;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriberTest extends TestCase
{
    private PaymentMethodDataRegistry&MockObject $methodDataRegistry;

    private EntityRepository&MockObject $transactionReportRepository;

    private OrderTransactionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->methodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $this->transactionReportRepository = $this->createMock(EntityRepository::class);

        $this->subscriber = new OrderTransactionSubscriber(
            $this->methodDataRegistry,
            $this->transactionReportRepository,
        );
    }

    public function testOnPaidStateTransition(): void
    {
        $order = $this->createOrder($this->createTransaction('transaction-id'));

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->methodDataRegistry
            ->expects(static::once())
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->transactionReportRepository
            ->expects(static::once())
            ->method('upsert')
            ->with([[
                'orderTransactionId' => 'transaction-id',
                'currencyIso' => 'EUR',
                'totalPrice' => 10,
            ]]);

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionWritesInSystemScope(): void
    {
        $order = $this->createOrder($this->createTransaction('transaction-id'));

        // e.g. a restricted integration without privileges on swag_paypal_transaction_report
        $context = new Context(new AdminApiSource(null, 'integration-id'));

        $event = new OrderStateMachineStateChangeEvent('paid', $order, $context);

        $this->methodDataRegistry
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->transactionReportRepository
            ->expects(static::once())
            ->method('upsert')
            ->with(static::anything(), static::callback(
                static fn (Context $context): bool => $context->getScope() === Context::SYSTEM_SCOPE
            ));

        $this->subscriber->onPaidStateTransition($event);

        static::assertSame(Context::USER_SCOPE, $context->getScope());
    }

    public function testOnPaidStateTransitionUsesPaidTransactionInsteadOfFirst(): void
    {
        $order = $this->createOrder(
            $this->createTransaction('cancelled-transaction-id', state: OrderTransactionStates::STATE_CANCELLED),
            $this->createTransaction('paid-transaction-id', amount: 20),
        );

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->methodDataRegistry
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->transactionReportRepository
            ->expects(static::once())
            ->method('upsert')
            ->with([[
                'orderTransactionId' => 'paid-transaction-id',
                'currencyIso' => 'EUR',
                'totalPrice' => 20,
            ]]);

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionIgnoresUnpaidPayPalTransactionBeforePaidPrepayment(): void
    {
        $order = $this->createOrder(
            $this->createTransaction('paypal-transaction-id', state: OrderTransactionStates::STATE_FAILED),
            $this->createTransaction('prepayment-transaction-id', PrePayment::class),
        );

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->methodDataRegistry
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionWithoutPaidTransaction(): void
    {
        $order = $this->createOrder(
            $this->createTransaction('transaction-id', state: OrderTransactionStates::STATE_OPEN),
        );

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->transactionReportRepository->expects(static::never())->method(static::anything());
        $this->methodDataRegistry->expects(static::never())->method(static::anything());

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionWithNonLiveVersion(): void
    {
        $order = $this->createOrder($this->createTransaction('transaction-id'));

        $context = new Context(
            new SystemSource(),
            versionId: 'random-non-live-version-id',
        );

        $event = new OrderStateMachineStateChangeEvent('paid', $order, $context);

        $this->transactionReportRepository->expects(static::never())->method(static::anything());
        $this->methodDataRegistry->expects(static::never())->method(static::anything());

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionWithoutPayPalPaymentHandler(): void
    {
        $order = $this->createOrder($this->createTransaction('transaction-id', 'not-a-paypal-handler'));

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->transactionReportRepository->expects(static::never())->method(static::anything());

        $this->methodDataRegistry
            ->expects(static::once())
            ->method('getPaymentHandlers')
            ->willReturn([PayPalHandler::class]);

        $this->subscriber->onPaidStateTransition($event);
    }

    public function testOnPaidStateTransitionWithSandboxTransaction(): void
    {
        $transaction = $this->createTransaction('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => true]);

        $order = $this->createOrder($transaction);

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->transactionReportRepository->expects(static::never())->method(static::anything());
        $this->methodDataRegistry->expects(static::never())->method(static::anything());

        $this->subscriber->onPaidStateTransition($event);
    }

    private function createTransaction(
        string $id,
        string $handlerIdentifier = PayPalHandler::class,
        string $state = OrderTransactionStates::STATE_PAID,
        float $amount = 10,
    ): OrderTransactionEntity {
        return (new OrderTransactionEntity())->assign([
            'id' => $id,
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => $handlerIdentifier]),
            'stateMachineState' => (new StateMachineStateEntity())->assign(['technicalName' => $state]),
            'amount' => new CalculatedPrice($amount, $amount, new CalculatedTaxCollection(), new TaxRuleCollection()),
        ]);
    }

    private function createOrder(OrderTransactionEntity ...$transactions): OrderEntity
    {
        return (new OrderEntity())->assign([
            'transactions' => new OrderTransactionCollection($transactions),
            'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
        ]);
    }
}

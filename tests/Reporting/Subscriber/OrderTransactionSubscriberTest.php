<?php

declare(strict_types=1);
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
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
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
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'transaction-id',
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => PayPalHandler::class]),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
        ]);

        $order = (new OrderEntity())->assign([
            'transactions' => new OrderTransactionCollection([$transaction]),
            'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
        ]);

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

    public function testOnPaidStateTransitionWithNonLiveVersion(): void
    {
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'transaction-id',
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => PayPalHandler::class]),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
        ]);

        $order = (new OrderEntity())->assign([
            'transactions' => new OrderTransactionCollection([$transaction]),
            'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
        ]);

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
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'transaction-id',
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => 'not-a-paypal-handler']),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
        ]);

        $order = (new OrderEntity())->assign([
            'transactions' => new OrderTransactionCollection([$transaction]),
            'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
        ]);

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
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'transaction-id',
            'paymentMethod' => (new PaymentMethodEntity())->assign(['handlerIdentifier' => PayPalHandler::class]),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => true],
        ]);

        $order = (new OrderEntity())->assign([
            'transactions' => new OrderTransactionCollection([$transaction]),
            'currency' => (new CurrencyEntity())->assign(['isoCode' => 'EUR']),
        ]);

        $event = new OrderStateMachineStateChangeEvent('paid', $order, Context::createDefaultContext());

        $this->transactionReportRepository->expects(static::never())->method(static::anything());
        $this->methodDataRegistry->expects(static::never())->method(static::anything());

        $this->subscriber->onPaidStateTransition($event);
    }
}

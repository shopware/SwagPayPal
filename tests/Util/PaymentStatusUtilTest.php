<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Swag\PayPal\RestApi\V1\Api\Capture;
use Swag\PayPal\RestApi\V1\Api\Common\Value;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;
use Swag\PayPal\RestApi\V1\Api\Payment\TransactionCollection;
use Swag\PayPal\RestApi\V1\Api\Refund;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Util\PaymentStatusUtil;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentStatusUtilTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderTransactionTrait;

    private const FIRST_TRANSACTION_ID = '9535b385fc7544f08e21b8b74b52ff4a';
    private const SECOND_TRANSACTION_ID = '8535b385fc7544f08e21b8b74b52ff4a';

    private PaymentStatusUtil $paymentStatusUtil;

    private InitialStateIdLoader $initialStateIdLoader;

    private EntityRepository $orderRepository;

    private EntityRepository $orderTransactionRepository;

    protected function setUp(): void
    {
        $container = $this->getContainer();
        $this->initialStateIdLoader = $this->getContainer()->get(InitialStateIdLoader::class);

        /** @var EntityRepository $orderRepository */
        $orderRepository = $container->get('order.repository');
        $this->orderRepository = $orderRepository;

        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $container->get('order_transaction.repository');
        $this->orderTransactionRepository = $orderTransactionRepository;

        $this->paymentStatusUtil = new PaymentStatusUtil(
            $this->orderRepository,
            $container->get(OrderTransactionStateHandler::class),
            new PriceFormatter()
        );
    }

    public function testApplyVoidStateToOrder(): void
    {
        $orderId = $this->createBasicOrder();
        $this->paymentStatusUtil->applyVoidStateToOrder($orderId, Context::createDefaultContext());

        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_CANCELLED);
    }

    public static function dataProviderTestApplyCaptureState(): array
    {
        $finalCaptureResponse = new Capture();
        $finalCaptureResponse->setIsFinalCapture(true);
        $notFinalCaptureResponse = new Capture();
        $notFinalCaptureResponse->setIsFinalCapture(false);

        return [
            [
                $finalCaptureResponse,
                OrderTransactionStates::STATE_PAID,
            ],
            [
                $notFinalCaptureResponse,
                OrderTransactionStates::STATE_PARTIALLY_PAID,
            ],
        ];
    }

    #[DataProvider('dataProviderTestApplyCaptureState')]
    public function testApplyCaptureState(Capture $captureResponse, string $expectedOrderTransactionState): void
    {
        $orderId = $this->createBasicOrder();
        $this->paymentStatusUtil->applyCaptureState(
            $orderId,
            $captureResponse,
            Context::createDefaultContext()
        );

        $this->assertTransactionState($orderId, $expectedOrderTransactionState);
    }

    public function testMultipleApplyCaptureState(): void
    {
        $orderId = $this->createBasicOrder();
        $context = Context::createDefaultContext();

        $firstCapture = new Capture();
        $firstCapture->setIsFinalCapture(false);

        $this->paymentStatusUtil->applyCaptureState($orderId, $firstCapture, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $secondCapture = new Capture();
        $secondCapture->setIsFinalCapture(true);

        $this->paymentStatusUtil->applyCaptureState($orderId, $secondCapture, $context);

        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PAID);
    }

    public function testSeveralCapturesAndRefundsWorkflow(): void
    {
        $orderId = $this->createBasicOrder();
        $context = Context::createDefaultContext();

        $firstCapture = new Capture();
        $firstCapture->setIsFinalCapture(false);

        $this->paymentStatusUtil->applyCaptureState($orderId, $firstCapture, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $partialRefundResponse = new Refund();
        $partialRefundResponse->assign(
            ['totalRefundedAmount' => (new Value())->assign(['value' => '2.00'])]
        );

        $transaction = new Transaction();
        $transaction->assign([
            'related_resources' => [
                ['capture' => ['amount' => ['total' => '10.00']]],
                ['refund' => ['amount' => ['total' => '2.00']]],
            ],
        ]);

        $paymentResponse = new Payment();
        $paymentResponse->setTransactions(new TransactionCollection([$transaction]));
        $this->paymentStatusUtil->applyRefundStateToCapture($orderId, $partialRefundResponse, $paymentResponse, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);

        $secondCapture = new Capture();
        $secondCapture->setIsFinalCapture(false);

        $this->paymentStatusUtil->applyCaptureState($orderId, $secondCapture, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $thirdCapture = new Capture();
        $thirdCapture->setIsFinalCapture(true);

        $this->paymentStatusUtil->applyCaptureState($orderId, $thirdCapture, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PAID);

        $secondPartialRefundResponse = new Refund();
        $secondPartialRefundResponse->assign(
            ['totalRefundedAmount' => (new Value())->assign(['value' => '4.00'])]
        );

        $secondTransaction = new Transaction();
        $secondTransaction->assign([
            'related_resources' => [
                ['capture' => ['amount' => ['total' => '10.00']]],
                ['refund' => ['amount' => ['total' => '2.00']]],
                ['capture' => ['amount' => ['total' => '2.00']]],
                ['refund' => ['amount' => ['total' => '2.00']]],
                ['capture' => ['amount' => ['total' => '2.00']]],
            ],
        ]);

        $secondPaymentResponse = new Payment();
        $secondPaymentResponse->setTransactions(new TransactionCollection([$secondTransaction]));
        $this->paymentStatusUtil->applyRefundStateToCapture($orderId, $secondPartialRefundResponse, $secondPaymentResponse, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);

        $thirdPartialRefundResponse = new Refund();
        $thirdPartialRefundResponse->assign(
            ['totalRefundedAmount' => (new Value())->assign(['value' => '14.00'])]
        );

        $thirdTransaction = new Transaction();
        $thirdTransaction->assign([
            'related_resources' => [
                ['capture' => ['amount' => ['total' => '10.00']]],
                ['refund' => ['amount' => ['total' => '2.00']]],
                ['capture' => ['amount' => ['total' => '2.00']]],
                ['refund' => ['amount' => ['total' => '2.00']]],
                ['capture' => ['amount' => ['total' => '2.00']]],
                ['refund' => ['amount' => ['total' => '10.00']]],
            ],
        ]);

        $thirdPaymentResponse = new Payment();
        $thirdPaymentResponse->setTransactions(new TransactionCollection([$thirdTransaction]));
        $this->paymentStatusUtil->applyRefundStateToCapture($orderId, $thirdPartialRefundResponse, $thirdPaymentResponse, $context);
        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_REFUNDED);
    }

    public static function dataProviderTestApplyRefundStateToPayment(): array
    {
        $completeRefundResponse = new Refund();
        $completeRefundResponse->assign(['totalRefundedAmount' => (new Value())->assign(['value' => '15'])]);

        $partialRefundResponse = new Refund();
        $partialRefundResponse->assign(['totalRefundedAmount' => (new Value())->assign(['value' => '14.99'])]);

        return [
            [
                $completeRefundResponse,
                OrderTransactionStates::STATE_REFUNDED,
            ],
            [
                $partialRefundResponse,
                OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
            ],
        ];
    }

    #[DataProvider('dataProviderTestApplyRefundStateToPayment')]
    public function testApplyRefundStateToPayment(Refund $refundResponse, string $expectedOrderTransactionState): void
    {
        $orderId = $this->createBasicOrder();
        $captureResponse = new Capture();
        $captureResponse->setIsFinalCapture(true);
        $context = Context::createDefaultContext();

        $this->paymentStatusUtil->applyCaptureState($orderId, $captureResponse, $context);

        $this->paymentStatusUtil->applyRefundStateToPayment($orderId, $refundResponse, $context);

        $this->assertTransactionState($orderId, $expectedOrderTransactionState);
    }

    public function testApplyRefundStateToCapture(): void
    {
        $orderId = $this->createBasicOrder();
        $captureResponse = new Capture();
        $captureResponse->setIsFinalCapture(true);
        $context = Context::createDefaultContext();

        $this->paymentStatusUtil->applyCaptureState($orderId, $captureResponse, $context);

        $completeRefundResponse = new Refund();
        $completeRefundResponse->assign(
            ['totalRefundedAmount' => (new Value())->assign(['value' => '15'])]
        );

        $this->paymentStatusUtil->applyRefundStateToCapture($orderId, $completeRefundResponse, new Payment(), $context);

        $this->assertTransactionState($orderId, OrderTransactionStates::STATE_REFUNDED);
    }

    public function testApplyVoidStateToOrderWithNoOrder(): void
    {
        $this->expectException(ShopwareHttpException::class);
        $this->paymentStatusUtil->applyVoidStateToOrder(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testApplyVoidStateToOrderWithNoOrderTransaction(): void
    {
        $orderId = $this->createBasicOrder(false);
        $this->expectException(PaymentException::class);
        $this->paymentStatusUtil->applyVoidStateToOrder($orderId, Context::createDefaultContext());
    }

    private function createBasicOrder(bool $withTransaction = true): string
    {
        $ids = new IdsCollection();
        $context = Context::createDefaultContext();

        $orderData = $this->getOrderData($ids);
        $this->orderRepository->create($orderData, $context);

        if ($withTransaction) {
            $firstTransactionData = [
                [
                    'id' => self::FIRST_TRANSACTION_ID,
                    'orderId' => $ids->get('order-id'),
                    'paymentMethodId' => $this->getValidPaymentMethodId(),
                    'amount' => [
                        'unitPrice' => 5.0,
                        'totalPrice' => 15.0,
                        'quantity' => 3,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                    ],
                    'stateId' => $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE),
                ],
            ];
            $secondTransactionData = [
                [
                    'id' => self::SECOND_TRANSACTION_ID,
                    'orderId' => $ids->get('order-id'),
                    'paymentMethodId' => $this->getValidPaymentMethodId(),
                    'amount' => [
                        'unitPrice' => 5.0,
                        'totalPrice' => 15.0,
                        'quantity' => 3,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                    ],
                    'stateId' => $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE),
                ],
            ];
            // Do not create simultaneously, so they have slightly different created dates
            $this->orderTransactionRepository->create($firstTransactionData, $context);
            $this->orderTransactionRepository->create($secondTransactionData, $context);

            $updateData = [
                [
                    'id' => $ids->get('order-id'),
                    'transactions' => [
                        ['id' => self::FIRST_TRANSACTION_ID],
                        ['id' => self::SECOND_TRANSACTION_ID],
                    ],
                ],
            ];

            $this->orderRepository->update($updateData, $context);
        }

        return $ids->get('order-id');
    }

    private function assertTransactionState(string $orderId, string $expectedOrderTransactionState): void
    {
        $changedOrder = $this->getOrder($orderId);
        static::assertNotNull($changedOrder);

        $orderTransactionCollection = $changedOrder->getTransactions();
        static::assertNotNull($orderTransactionCollection);

        $orderTransactionEntity = $orderTransactionCollection->get(self::SECOND_TRANSACTION_ID);
        static::assertInstanceOf(OrderTransactionEntity::class, $orderTransactionEntity);

        $stateMachineState = $orderTransactionEntity->getStateMachineState();
        static::assertInstanceOf(StateMachineStateEntity::class, $stateMachineState);
        static::assertSame($expectedOrderTransactionState, $stateMachineState->getTechnicalName());
    }

    private function getOrder(string $orderId): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.stateMachineState');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        return $order;
    }
}

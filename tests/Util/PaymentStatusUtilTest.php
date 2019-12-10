<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\PayPalPaymentController;
use Swag\PayPal\Util\PaymentStatusUtil;
use Symfony\Component\HttpFoundation\Request;

class PaymentStatusUtilTest extends TestCase
{
    use KernelTestBehaviour;
    use DatabaseTransactionBehaviour;
    use OrderFixture;

    /**
     * @var PaymentStatusUtil
     */
    private $paymentStatusUtil;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    protected function setUp(): void
    {
        $container = $this->getContainer();
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $container->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
        /** @var EntityRepositoryInterface $orderRepository */
        $orderRepository = $container->get('order.repository');
        /** @var OrderTransactionStateHandler $orderTransactionStateHandler */
        $orderTransactionStateHandler = $container->get(OrderTransactionStateHandler::class);

        $this->paymentStatusUtil = new PaymentStatusUtil(
            $orderRepository,
            $orderTransactionStateHandler
        );
    }

    public function testApplyVoidStateToOrder(): void
    {
        $orderId = $this->createBasicOrder();

        $this->paymentStatusUtil->applyVoidStateToOrder($orderId, Context::createDefaultContext());

        /** @var mixed $changedOrder */
        $changedOrder = $this->getOrder($orderId);
        static::assertInstanceOf(OrderEntity::class, $changedOrder);

        $orderTransactionCollection = $changedOrder->getTransactions();
        static::assertNotNull($orderTransactionCollection);

        $orderTransactionEntity = $orderTransactionCollection->first();
        static::assertInstanceOf(OrderTransactionEntity::class, $orderTransactionEntity);

        $stateMachineState = $orderTransactionEntity->getStateMachineState();
        static::assertInstanceOf(StateMachineStateEntity::class, $stateMachineState);
        static::assertSame(OrderTransactionStates::STATE_CANCELLED, $stateMachineState->getTechnicalName());
    }

    public function dataProviderTestApplyCaptureStateToPayment(): array
    {
        return [
            [
                new Request([], [
                    PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_AMOUNT => 7.0,
                    PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_IS_FINAL => true,
                ]),
                OrderTransactionStates::STATE_PAID,
            ],
            [
                new Request([], [
                    PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_AMOUNT => 15.0,
                ]),
                OrderTransactionStates::STATE_PAID,
            ],
            [
                new Request([], [
                    PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_AMOUNT => 14.99,
                ]),
                OrderTransactionStates::STATE_PARTIALLY_PAID,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestApplyCaptureStateToPayment
     */
    public function testApplyCaptureStateToPayment(Request $request, string $expectedOrderTransactionState): void
    {
        $orderId = $this->createBasicOrder();
        $this->paymentStatusUtil->applyCaptureStateToPayment($orderId, $request, Context::createDefaultContext());

        /** @var mixed $changedOrder */
        $changedOrder = $this->getOrder($orderId);
        static::assertInstanceOf(OrderEntity::class, $changedOrder);

        $orderTransactionCollection = $changedOrder->getTransactions();
        static::assertNotNull($orderTransactionCollection);

        $orderTransactionEntity = $orderTransactionCollection->first();
        static::assertInstanceOf(OrderTransactionEntity::class, $orderTransactionEntity);

        $stateMachineState = $orderTransactionEntity->getStateMachineState();
        static::assertInstanceOf(StateMachineStateEntity::class, $stateMachineState);
        static::assertSame($expectedOrderTransactionState, $stateMachineState->getTechnicalName());
    }

    public function dataProviderTestApplyRefundStateToPayment(): array
    {
        return [
            [
                new Request([], [
                    PayPalPaymentController::REQUEST_PARAMETER_REFUND_AMOUNT => 15.0,
                ]),
                OrderTransactionStates::STATE_REFUNDED,
            ],
            [
                new Request([], [
                    PayPalPaymentController::REQUEST_PARAMETER_REFUND_AMOUNT => 14.99,
                ]),
                OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestApplyRefundStateToPayment
     */
    public function testApplyRefundStateToPayment(Request $request, string $expectedOrderTransactionState): void
    {
        $orderId = $this->createBasicOrder();
        $captureRequest = new Request([], [
            PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_AMOUNT => 15.0,
            PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_IS_FINAL => true,
        ]);
        $this->paymentStatusUtil->applyCaptureStateToPayment($orderId, $captureRequest, Context::createDefaultContext());
        $this->paymentStatusUtil->applyRefundStateToPayment($orderId, $request, Context::createDefaultContext());

        /** @var mixed $changedOrder */
        $changedOrder = $this->getOrder($orderId);
        static::assertInstanceOf(OrderEntity::class, $changedOrder);

        $orderTransactionCollection = $changedOrder->getTransactions();
        static::assertNotNull($orderTransactionCollection);

        $orderTransactionEntity = $orderTransactionCollection->first();
        static::assertInstanceOf(OrderTransactionEntity::class, $orderTransactionEntity);

        $stateMachineState = $orderTransactionEntity->getStateMachineState();
        static::assertInstanceOf(StateMachineStateEntity::class, $stateMachineState);
        static::assertSame($expectedOrderTransactionState, $stateMachineState->getTechnicalName());
    }

    public function testApplyVoidStateToOrderWithNoOrder(): void
    {
        $this->expectException(OrderNotFoundException::class);
        $this->paymentStatusUtil->applyVoidStateToOrder(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testApplyVoidStateToOrderWithNoOrderTransaction(): void
    {
        $orderId = $this->createBasicOrder(false);
        $this->expectException(InvalidOrderException::class);
        $this->paymentStatusUtil->applyVoidStateToOrder($orderId, Context::createDefaultContext());
    }

    private function createBasicOrder(bool $withTransaction = true): string
    {
        /** @var EntityRepositoryInterface $orderRepo */
        $orderRepo = $this->getContainer()->get('order.repository');
        /** @var EntityRepositoryInterface $orderTransactionRepo */
        $orderTransactionRepo = $this->getContainer()->get('order_transaction.repository');
        $orderId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $orderData = $this->getOrderData($orderId, $context);
        $orderRepo->create($orderData, $context);

        if ($withTransaction) {
            $orderTransactionData = [
                [
                    'id' => Uuid::randomHex(),
                    'orderId' => $orderId,
                    'paymentMethodId' => $this->getValidPaymentMethodId(),
                    'amount' => [
                        'unitPrice' => 5.0,
                        'totalPrice' => 15.0,
                        'quantity' => 3,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                    ],
                    'stateId' => $this->stateMachineRegistry->getInitialState(
                        OrderTransactionStates::STATE_MACHINE,
                        $context
                    )->getId(),
                ],
            ];
            $orderTransactionRepo->create($orderTransactionData, $context);

            $updateData = [
                [
                    'id' => $orderId,
                    'transactions' => $orderTransactionData,
                ],
            ];

            $orderRepo->update($updateData, $context);
        }

        return $orderId;
    }

    private function getOrder(string $orderId): ?OrderEntity
    {
        /** @var EntityRepositoryInterface $orderRepo */
        $orderRepo = $this->getContainer()->get('order.repository');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        return $orderRepo->search($criteria, Context::createDefaultContext())->get($orderId);
    }
}

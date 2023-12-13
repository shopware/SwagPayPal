<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\CaptureCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund\SellerPayableBreakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\RefundCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentStatusUtilV2Test extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderTransactionTrait;
    use StateMachineStateTrait;

    private EntityRepository $orderTransactionRepository;

    private PaymentStatusUtilV2 $paymentStatusUtil;

    private Context $context;

    protected function setUp(): void
    {
        $container = $this->getContainer();

        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $container->get('order_transaction.repository');
        $this->orderTransactionRepository = $orderTransactionRepository;

        $this->paymentStatusUtil = new PaymentStatusUtilV2(
            $this->orderTransactionRepository,
            $container->get(OrderTransactionStateHandler::class),
            new PriceFormatter()
        );

        $this->context = Context::createDefaultContext();
    }

    /**
     * @dataProvider dataProviderTestApplyCaptureState
     */
    public function testApplyCaptureState(Capture $captureResponse, string $expectedOrderTransactionState, string $originalOrderTransactionState): void
    {
        $orderTransactionId = $this->createOrderTransaction(true, $originalOrderTransactionState);

        $this->paymentStatusUtil->applyCaptureState(
            $orderTransactionId,
            $captureResponse,
            $this->context
        );

        $this->assertTransactionState($orderTransactionId, $expectedOrderTransactionState);
    }

    public function dataProviderTestApplyCaptureState(): array
    {
        return [
            [
                $this->createCapture(true),
                OrderTransactionStates::STATE_PAID,
                OrderTransactionStates::STATE_AUTHORIZED,
            ],
            [
                $this->createCapture(false),
                OrderTransactionStates::STATE_PARTIALLY_PAID,
                OrderTransactionStates::STATE_AUTHORIZED,
            ],
            [
                $this->createCapture(true),
                OrderTransactionStates::STATE_PAID,
                OrderTransactionStates::STATE_PAID,
            ],
            [
                $this->createCapture(true),
                OrderTransactionStates::STATE_PAID,
                OrderTransactionStates::STATE_UNCONFIRMED,
            ],
            [
                $this->createCapture(true),
                OrderTransactionStates::STATE_PAID,
                OrderTransactionStates::STATE_PARTIALLY_PAID,
            ],
            [
                $this->createCapture(true),
                OrderTransactionStates::STATE_PAID,
                OrderTransactionStates::STATE_CANCELLED,
            ],
            [
                $this->createCapture(false),
                OrderTransactionStates::STATE_PARTIALLY_PAID,
                OrderTransactionStates::STATE_UNCONFIRMED,
            ],
        ];
    }

    public function testApplyCaptureStateThrowsExceptionWithoutTransaction(): void
    {
        $orderTransactionId = $this->createOrderTransaction(false);

        $captureResponse = $this->createCapture(true);
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            \sprintf('The transaction with id %s is invalid or could not be found.', $orderTransactionId)
        );
        $this->paymentStatusUtil->applyCaptureState(
            $orderTransactionId,
            $captureResponse,
            $this->context
        );
    }

    public function testApplyVoidState(): void
    {
        $orderTransactionId = $this->createOrderTransaction();

        $this->paymentStatusUtil->applyVoidState(
            $orderTransactionId,
            $this->context
        );

        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_CANCELLED);
    }

    /**
     * @dataProvider dataProviderTestApplyRefundState
     */
    public function testApplyRefundState(Refund $refundResponse, string $expectedOrderTransactionState): void
    {
        $orderTransactionId = $this->createOrderTransaction();

        $captureResponse = $this->createCapture(true);

        $this->paymentStatusUtil->applyCaptureState(
            $orderTransactionId,
            $captureResponse,
            $this->context
        );

        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PAID);

        $order = $this->createOrder();
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $refundResponse, $order, $this->context);

        $this->assertTransactionState($orderTransactionId, $expectedOrderTransactionState);
    }

    public function dataProviderTestApplyRefundState(): array
    {
        return [
            [
                $this->createRefund('15.00', '15.00'),
                OrderTransactionStates::STATE_REFUNDED,
            ],
            [
                $this->createRefund('14.00', '14.00'),
                OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
            ],
        ];
    }

    public function testPartialNotFinalCaptureAndFullRefund(): void
    {
        $orderTransactionId = $this->createOrderTransaction();

        $capture = $this->createCapture(false, '10.00');
        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $capture, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $refund = $this->createRefund('10.00', '10.00');
        $firstOrder = $this->createOrder(new CaptureCollection([$capture]), new RefundCollection([$refund]));
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $refund, $firstOrder, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);
    }

    public function testPartialFinalCaptureAndFullRefund(): void
    {
        $orderTransactionId = $this->createOrderTransaction();

        $capture = $this->createCapture(true, '10.00');
        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $capture, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PAID);

        $refund = $this->createRefund('10.00', '10.00');
        $firstOrder = $this->createOrder(new CaptureCollection([$capture]), new RefundCollection([$refund]));
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $refund, $firstOrder, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_REFUNDED);
    }

    public function testSeveralCapturesAndRefundsWorkflow(): void
    {
        $orderTransactionId = $this->createOrderTransaction();

        $firstCapture = $this->createCapture(false, '10.00');
        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $firstCapture, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $firstPartialRefund = $this->createRefund('2.00', '2.00');
        $firstOrder = $this->createOrder(new CaptureCollection([$firstCapture]), new RefundCollection([$firstPartialRefund]));
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $firstPartialRefund, $firstOrder, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);

        $secondCapture = $this->createCapture(false, '2.00');
        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $secondCapture, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_PAID);

        $thirdCapture = $this->createCapture(true, '2.00');
        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $thirdCapture, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PAID);

        $secondPartialRefund = $this->createRefund('2.00', '4.00');
        $secondOrder = $this->createOrder(
            new CaptureCollection([$firstCapture, $secondCapture, $thirdCapture]),
            new RefundCollection([$firstPartialRefund, $secondPartialRefund])
        );
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $secondPartialRefund, $secondOrder, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);

        $thirdPartialRefund = $this->createRefund('10.00', '14.00');
        $thirdOrder = $this->createOrder(
            new CaptureCollection([$firstCapture, $secondCapture, $thirdCapture]),
            new RefundCollection([$firstPartialRefund, $secondPartialRefund, $thirdPartialRefund])
        );
        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $thirdPartialRefund, $thirdOrder, $this->context);
        $this->assertTransactionState($orderTransactionId, OrderTransactionStates::STATE_REFUNDED);
    }

    private function createOrderTransaction(bool $withTransaction = true, string $state = OrderTransactionStates::STATE_AUTHORIZED): string
    {
        $orderTransactionId = Uuid::randomHex();

        $orderData = $this->getOrderData(new IdsCollection());
        if ($withTransaction) {
            $transactionData = [
                [
                    'id' => $orderTransactionId,
                    'paymentMethodId' => $this->getValidPaymentMethodId(),
                    'amount' => [
                        'unitPrice' => 5.0,
                        'totalPrice' => 15.0,
                        'quantity' => 3,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                    ],
                    'stateId' => $this->getOrderTransactionStateIdByTechnicalName(
                        $state,
                        $this->getContainer(),
                        $this->context
                    ),
                ],
            ];

            $orderData[0]['transactions'] = $transactionData;
        }

        /** @var EntityRepository $orderRepository */
        $orderRepository = $this->getContainer()->get('order.repository');
        $orderRepository->create($orderData, $this->context);

        return $orderTransactionId;
    }

    private function assertTransactionState(string $orderTransactionId, string $expectedOrderTransactionState): void
    {
        $orderTransaction = $this->getOrderTransaction($orderTransactionId);
        static::assertNotNull($orderTransaction);

        $stateMachineState = $orderTransaction->getStateMachineState();
        static::assertNotNull($stateMachineState);
        static::assertSame($expectedOrderTransactionState, $stateMachineState->getTechnicalName());
    }

    private function getOrderTransaction(string $orderTransactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);

        /** @var ?OrderTransactionEntity $entity */
        $entity = $this->orderTransactionRepository->search($criteria, $this->context)->first();

        return $entity;
    }

    private function createCapture(bool $isFinal, ?string $value = null): Capture
    {
        $capture = new Capture();
        $capture->setFinalCapture($isFinal);
        if ($value !== null) {
            $captureAmount = new Money();
            $captureAmount->setValue($value);
            $captureAmount->setCurrencyCode('EUR');

            $capture->setAmount($captureAmount);
        }

        return $capture;
    }

    private function createRefund(string $value, string $totalRefunded): Refund
    {
        $totalRefundedAmount = new Money();
        $totalRefundedAmount->setValue($totalRefunded);
        $totalRefundedAmount->setCurrencyCode('EUR');

        $sellerPayableBreakDown = new SellerPayableBreakdown();
        $sellerPayableBreakDown->setTotalRefundedAmount($totalRefundedAmount);

        $refundAmount = new Money();
        $refundAmount->setValue($value);
        $refundAmount->setCurrencyCode('EUR');

        $refund = new Refund();
        $refund->setSellerPayableBreakdown($sellerPayableBreakDown);
        $refund->setAmount($refundAmount);

        return $refund;
    }

    private function createOrder(?CaptureCollection $captures = null, ?RefundCollection $refunds = null): Order
    {
        $order = new Order();
        $purchaseUnit = new PurchaseUnit();
        $payments = new Payments();
        $payments->setCaptures($captures);
        $payments->setRefunds($refunds);
        $purchaseUnit->setPayments($payments);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        return $order;
    }
}

<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Core\Checkout\Payment\Cart\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\StateMachine\StateMachineRegistry;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Payment\PaymentBuilderInterface;
use SwagPayPal\PayPal\PaymentIntent;
use SwagPayPal\PayPal\PaymentStatus;
use SwagPayPal\PayPal\Resource\PaymentResource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPayment implements PaymentHandlerInterface
{
    public const TRANSACTION_DETAILS_JSON_KEY = 'swag_paypal';

    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';

    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PaymentBuilderInterface
     */
    private $paymentBuilder;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        PaymentResource $paymentResource,
        PaymentBuilderInterface $paymentBuilder,
        StateMachineRegistry $stateMachineRegistry
    ) {
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->paymentResource = $paymentResource;
        $this->paymentBuilder = $paymentBuilder;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    public function pay(PaymentTransactionStruct $transaction, Context $context): ?RedirectResponse
    {
        $payment = $this->paymentBuilder->getPayment($transaction, $context);

        $response = $this->paymentResource->create($payment, $context);

        $data = [
            'id' => $transaction->getTransactionId(),
            'details' => [
                self::TRANSACTION_DETAILS_JSON_KEY => [
                    'transactionId' => $response->getId(),
                ],
            ],
        ];
        $this->orderTransactionRepo->update([$data], $context);

        return new RedirectResponse($response->getLinks()[1]->getHref());
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        if ($request->query->getBoolean('cancel')) {
            $stateId = $this->stateMachineRegistry->getStateByTechnicalName(
                Defaults::ORDER_TRANSACTION_STATE_MACHINE,
                Defaults::ORDER_TRANSACTION_STATES_CANCELLED,
                $context
            )->getId();

            $transaction = [
                'id' => $transactionId,
                'stateId' => $stateId,
            ];
            $this->orderTransactionRepo->update([$transaction], $context);

            return;
        }

        $payerId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        $paymentId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        $response = $this->paymentResource->execute($payerId, $paymentId, $context);

        $paymentState = $this->getPaymentState($response);

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatus::PAYMENT_COMPLETED) {
            $stateId = $this->stateMachineRegistry->getStateByTechnicalName(
                Defaults::ORDER_TRANSACTION_STATE_MACHINE,
                Defaults::ORDER_TRANSACTION_STATES_PAID,
                $context
            )->getId();
        } else {
            $stateId = $this->stateMachineRegistry->getStateByTechnicalName(
                Defaults::ORDER_TRANSACTION_STATE_MACHINE,
                Defaults::ORDER_TRANSACTION_STATES_OPEN,
                $context
            )->getId();
        }

        $transaction = [
            'id' => $transactionId,
            'stateId' => $stateId,
        ];

        $this->orderTransactionRepo->update([$transaction], $context);
    }

    private function getPaymentState(Payment $response): string
    {
        $intent = $response->getIntent();
        $relatedResource = $response->getTransactions()[0]->getRelatedResources()[0];
        $paymentState = '';

        switch ($intent) {
            case PaymentIntent::SALE:
                $sale = $relatedResource->getSale();
                if ($sale !== null) {
                    $paymentState = $sale->getState();
                }
                break;
            case PaymentIntent::AUTHORIZE:
                $authorization = $relatedResource->getAuthorization();
                if ($authorization !== null) {
                    $paymentState = $authorization->getState();
                }
                break;
            case PaymentIntent::ORDER:
                $order = $relatedResource->getOrder();
                if ($order !== null) {
                    $paymentState = $order->getState();
                }
                break;
        }

        return $paymentState;
    }
}

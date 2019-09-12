<?php declare(strict_types=1);

namespace Swag\PayPal\Payment;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;
use Shopware\Core\System\StateMachine\Exception\StateMachineStateNotFoundException;
use Swag\PayPal\Payment\Exception\CurrencyNotFoundException;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPuiPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /**
     * @var PayPalHandler
     */
    private $payPalHandler;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    public function __construct(
        PayPalHandler $payPalHandler,
        PaymentResource $paymentResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EntityRepositoryInterface $orderTransactionRepo
    ) {
        $this->payPalHandler = $payPalHandler;
        $this->paymentResource = $paymentResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        try {
            $response = $this->payPalHandler->handlePayPalPayment($transaction, $salesChannelContext, $customer, true);
        } catch (AddressNotFoundException | CurrencyNotFoundException | InvalidOrderException
            | InconsistentCriteriaIdsException | PayPalSettingsInvalidException $e
        ) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $e->getMessage());
        }

        return new RedirectResponse($response->getLinks()[1]->getHref());
    }

    /**
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineNotFoundException
     * @throws StateMachineStateNotFoundException
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $transactionId = $transaction->getOrderTransaction()->getId();

        if ($request->query->getBoolean('cancel')) {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        $payerId = $request->query->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        $paymentId = $request->query->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);

        try {
            $response = $this->paymentResource->execute(
                $payerId,
                $paymentId,
                $salesChannelContext->getSalesChannel()->getId()
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }

        $paymentState = $this->getPaymentState($response);
        $context = $salesChannelContext->getContext();

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatus::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->pay($transactionId, $context);
        }

        $this->savePaymentInstructions($response, $transactionId, $context);
    }

    private function getPaymentState(Payment $payment): string
    {
        $paymentState = '';
        $sale = $payment->getTransactions()[0]->getRelatedResources()[0]->getSale();
        if ($sale !== null) {
            $paymentState = $sale->getState();
        }

        return $paymentState;
    }

    private function savePaymentInstructions(Payment $payment, string $transactionId, Context $context): void
    {
        $paymentInstructions = $payment->getPaymentInstruction();
        if ($paymentInstructions === null
            || $paymentInstructions->getInstructionType() !== PaymentInstruction::TYPE_INVOICE
        ) {
            return;
        }

        $data = [
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION => $paymentInstructions,
            ],
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}

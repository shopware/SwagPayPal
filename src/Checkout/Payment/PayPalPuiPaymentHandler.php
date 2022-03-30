<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.0.0 - will be removed, old PUI has been deprecated
 */
class PayPalPuiPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    private PlusPuiHandler $plusPuiHandler;

    private PaymentResource $paymentResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private EntityRepositoryInterface $orderTransactionRepo;

    private LoggerInterface $logger;

    private SettingsValidationServiceInterface $settingsValidationService;

    public function __construct(
        PlusPuiHandler $plusPuiHandler,
        PaymentResource $paymentResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EntityRepositoryInterface $orderTransactionRepo,
        LoggerInterface $logger,
        SettingsValidationServiceInterface $settingsValidationService
    ) {
        $this->plusPuiHandler = $plusPuiHandler;
        $this->paymentResource = $paymentResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->logger = $logger;
        $this->settingsValidationService = $settingsValidationService;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new AsyncPaymentProcessException(
                $transactionId,
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $exception) {
            throw new AsyncPaymentProcessException($transactionId, $exception->getMessage());
        }

        if (\method_exists($this->orderTransactionStateHandler, 'processUnconfirmed')) {
            $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());
        } else {
            $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());
        }

        try {
            $response = $this->plusPuiHandler->handlePuiPayment($transaction, $salesChannelContext, $customer);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
        }

        return new RedirectResponse($response->getLinks()[1]->getHref());
    }

    /**
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();
        $context = $salesChannelContext->getContext();

        if ($request->query->getBoolean(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL)) {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $exception) {
            throw new AsyncPaymentFinalizeException($transactionId, $exception->getMessage());
        }

        $payerId = $request->query->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        if (!\is_string($payerId)) {
            throw new MissingRequestParameterException(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        }

        $paymentId = $request->query->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        if (!\is_string($paymentId)) {
            throw new MissingRequestParameterException(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        }

        try {
            $response = $this->paymentResource->execute(
                $payerId,
                $paymentId,
                $salesChannelContext->getSalesChannel()->getId()
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $paymentState = $this->getPaymentState($response);

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatusV1::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->paid($transactionId, $context);
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

        $this->orderTransactionRepo->update([[
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION => $paymentInstructions,
            ],
        ]], $context);
    }
}

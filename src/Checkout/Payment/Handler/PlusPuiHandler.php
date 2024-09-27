<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PaymentsApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\TransactionPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 */
#[Package('checkout')]
class PlusPuiHandler
{
    public const PAYPAL_PAYMENT_ID_INPUT_NAME = 'paypalPaymentId';
    public const PAYPAL_PAYMENT_TOKEN_INPUT_NAME = 'paypalToken';

    private PaymentResource $paymentResource;

    private EntityRepository $orderTransactionRepo;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private TransactionPatchBuilder $transactionPatchBuilder;

    private PayerInfoPatchBuilder $payerInfoPatchBuilder;

    private ShippingAddressPatchBuilder $shippingAddressPatchBuilder;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        PaymentResource $paymentResource,
        EntityRepository $orderTransactionRepo,
        PayerInfoPatchBuilder $payerInfoPatchBuilder,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        TransactionPatchBuilder $transactionPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        LoggerInterface $logger,
    ) {
        $this->paymentResource = $paymentResource;
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->transactionPatchBuilder = $transactionPatchBuilder;
        $this->payerInfoPatchBuilder = $payerInfoPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->logger = $logger;
    }

    public function handlePlusPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer,
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalPaymentId = $dataBag->get(self::PAYPAL_PAYMENT_ID_INPUT_NAME);
        $paypalToken = $dataBag->get(self::PAYPAL_PAYMENT_TOKEN_INPUT_NAME);
        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $salesChannelContext->getContext(), $paypalToken);

        $patches = $this->transactionPatchBuilder->createTransactionPatch(
            $transaction,
            $salesChannelContext
        );

        $patches[] = $this->shippingAddressPatchBuilder->createShippingAddressPatch($transaction->getOrder());
        $patches[] = $this->payerInfoPatchBuilder->createPayerInfoPatch($transaction->getOrder());

        $this->patchPayPalPayment(
            $patches,
            $paypalPaymentId,
            $salesChannelContext->getSalesChannel()->getId(),
            $transaction->getOrderTransaction()->getId()
        );

        return new RedirectResponse('plusPatched');
    }

    /**
     * @throws PaymentException
     */
    public function handleFinalizePayment(
        AsyncPaymentTransactionStruct $transaction,
        string $salesChannelId,
        Context $context,
        string $paymentId,
        string $payerId,
        string $partnerAttributionId,
    ): void {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderNumber = $transaction->getOrder()->getOrderNumber();

        try {
            $response = $this->paymentResource->execute(
                $payerId,
                $paymentId,
                $salesChannelId,
                $partnerAttributionId
            );
        } catch (PayPalApiException $e) {
            $parameters = $e->getParameters();
            if (!isset($parameters['name']) || $parameters['name'] !== PayPalApiException::ERROR_CODE_DUPLICATE_ORDER_NUMBER) {
                throw PaymentException::asyncFinalizeInterrupted(
                    $transactionId,
                    \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
                );
            }

            $this->logger->warning('Duplicate order number {orderNumber} detected. Retrying payment without order number.', ['orderNumber' => $orderNumber]);

            try {
                $this->paymentResource->patch(
                    [
                        $this->orderNumberPatchBuilder->createOrderNumberPatch(null),
                    ],
                    $paymentId,
                    $salesChannelId
                );

                $response = $this->paymentResource->execute(
                    $payerId,
                    $paymentId,
                    $salesChannelId,
                    $partnerAttributionId
                );
            } catch (\Exception $e) {
                throw PaymentException::asyncFinalizeInterrupted(
                    $transactionId,
                    \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
                );
            }
        } catch (\Exception $e) {
            throw PaymentException::asyncFinalizeInterrupted(
                $transactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $paymentState = $this->getPaymentState($response);

        // apply the payment status if it's completed by PayPal
        if ($paymentState === PaymentStatusV1::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->paid($transactionId, $context);
        }

        if ($paymentState === PaymentStatusV1::PAYMENT_DENIED) {
            $this->orderTransactionStateHandler->fail($transactionId, $context);
        }

        $this->updateTransaction($response, $transactionId, $context);
    }

    /**
     * @param Patch[] $patches
     *
     * @throws PaymentException
     */
    private function patchPayPalPayment(
        array $patches,
        string $paypalPaymentId,
        string $salesChannelId,
        string $orderTransactionId,
    ): void {
        try {
            $this->paymentResource->patch($patches, $paypalPaymentId, $salesChannelId);
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }
    }

    private function addPayPalTransactionId(
        AsyncPaymentTransactionStruct $transaction,
        string $paypalPaymentId,
        Context $context,
        ?string $paypalToken = null,
    ): void {
        $customFields = [
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID => $paypalPaymentId,
        ];

        if ($paypalToken !== null) {
            $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TOKEN] = $paypalToken;
        }

        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }

    private function getPaymentState(Payment $payment): string
    {
        $intent = $payment->getIntent();
        $relatedResource = $payment->getTransactions()->first()?->getRelatedResources()->first();
        $paymentState = '';

        if ($relatedResource === null) {
            return $paymentState;
        }

        switch ($intent) {
            case PaymentIntentV1::SALE:
                $sale = $relatedResource->getSale();
                if ($sale !== null) {
                    $paymentState = $sale->getState();
                }

                break;
            case PaymentIntentV1::AUTHORIZE:
                $authorization = $relatedResource->getAuthorization();
                if ($authorization !== null) {
                    $paymentState = $authorization->getState();
                }

                break;
            case PaymentIntentV1::ORDER:
                $order = $relatedResource->getOrder();
                if ($order !== null) {
                    $paymentState = $order->getState();
                }

                break;
        }

        return $paymentState;
    }

    private function updateTransaction(Payment $payment, string $transactionId, Context $context): void
    {
        $customFields = [];

        $paymentInstructions = $payment->getPaymentInstruction();
        if ($paymentInstructions !== null
            && $paymentInstructions->getInstructionType() === PaymentInstruction::TYPE_INVOICE
        ) {
            $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION] = $paymentInstructions;
        }

        switch ($payment->getIntent()) {
            case PaymentIntentV1::ORDER:
                $resource = $payment->getTransactions()->first()?->getRelatedResources()->first()?->getOrder();

                break;
            case PaymentIntentV1::AUTHORIZE:
                $resource = $payment->getTransactions()->first()?->getRelatedResources()->first()?->getAuthorization();

                break;
            case PaymentIntentV1::SALE:
                $resource = $payment->getTransactions()->first()?->getRelatedResources()->first()?->getSale();

                break;
            default:
                $resource = null;
        }

        if ($resource !== null) {
            $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID] = $resource->getId();
        }

        if (empty($customFields)) {
            return;
        }

        $this->orderTransactionRepo->update([[
            'id' => $transactionId,
            'customFields' => $customFields,
        ]], $context);
    }
}

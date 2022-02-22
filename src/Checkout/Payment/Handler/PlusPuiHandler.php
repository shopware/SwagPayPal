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
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilderInterface;
use Swag\PayPal\PaymentsApi\Patch\CustomTransactionPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlusPuiHandler
{
    public const PAYPAL_PAYMENT_ID_INPUT_NAME = 'paypalPaymentId';
    public const PAYPAL_PAYMENT_TOKEN_INPUT_NAME = 'paypalToken';

    private PaymentResource $paymentResource;

    private EntityRepositoryInterface $orderTransactionRepo;

    private OrderPaymentBuilderInterface $paymentBuilder;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private CustomTransactionPatchBuilder $customTransactionPatchBuilder;

    private PayerInfoPatchBuilder $payerInfoPatchBuilder;

    private ShippingAddressPatchBuilder $shippingAddressPatchBuilder;

    private SystemConfigService $systemConfigService;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private LoggerInterface $logger;

    public function __construct(
        PaymentResource $paymentResource,
        EntityRepositoryInterface $orderTransactionRepo,
        OrderPaymentBuilderInterface $paymentBuilder,
        PayerInfoPatchBuilder $payerInfoPatchBuilder,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        CustomTransactionPatchBuilder $customTransactionPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder,
        SystemConfigService $systemConfigService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        LoggerInterface $logger
    ) {
        $this->paymentResource = $paymentResource;
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->paymentBuilder = $paymentBuilder;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->customTransactionPatchBuilder = $customTransactionPatchBuilder;
        $this->payerInfoPatchBuilder = $payerInfoPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
        $this->systemConfigService = $systemConfigService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->logger = $logger;
    }

    /**
     * @deprecated tag:v6.0.0 - Will be removed without replacement.
     */
    public function handlePlusPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalPaymentId = $dataBag->get(self::PAYPAL_PAYMENT_ID_INPUT_NAME);
        $paypalToken = $dataBag->get(self::PAYPAL_PAYMENT_TOKEN_INPUT_NAME);
        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $salesChannelContext->getContext(), $paypalToken);

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->payerInfoPatchBuilder->createPayerInfoPatch($customer),
            $this->customTransactionPatchBuilder->createCustomTransactionPatch($transaction->getOrderTransaction()->getId()),
        ];

        $this->patchPayPalPayment(
            $patches,
            $paypalPaymentId,
            $salesChannelContext->getSalesChannel()->getId(),
            $transaction->getOrderTransaction()->getId()
        );

        return new RedirectResponse('plusPatched');
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed, old PUI has been deprecated
     *
     * @throws AsyncPaymentProcessException
     */
    public function handlePuiPayment(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Payment {
        $this->logger->debug('Started');
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $orderTransactionId = $transaction->getOrderTransaction()->getId();

        $payment = $this->paymentBuilder->getPayment($transaction, $salesChannelContext);
        $payment->getPayer()->setExternalSelectedFundingInstrumentType(PaymentInstruction::TYPE_INVOICE);
        $payment->getApplicationContext()->setLocale('de_DE');

        try {
            $response = $this->paymentResource->create(
                $payment,
                $salesChannelId,
                PartnerAttributionId::PAYPAL_CLASSIC
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $paypalPaymentId = $response->getId();
        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->payerInfoPatchBuilder->createPayerInfoPatch($customer),
        ];

        $this->patchPayPalPayment($patches, $paypalPaymentId, $salesChannelId, $orderTransactionId);

        $context = $salesChannelContext->getContext();
        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $context);

        return $response;
    }

    /**
     * @throws AsyncPaymentFinalizeException
     */
    public function handleFinalizePayment(
        AsyncPaymentTransactionStruct $transaction,
        string $salesChannelId,
        Context $context,
        string $paymentId,
        string $payerId,
        string $partnerAttributionId,
        bool $orderNumberSendNeeded
    ): void {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderNumber = $transaction->getOrder()->getOrderNumber();

        if ($orderNumberSendNeeded && $orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelId)) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelId);
            $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $salesChannelId);
            $orderNumber = $orderNumberPrefix . $orderNumber . $orderNumberSuffix;

            try {
                $this->paymentResource->patch(
                    [
                        $this->orderNumberPatchBuilder->createOrderNumberPatch($orderNumber),
                    ],
                    $paymentId,
                    $salesChannelId
                );
            } catch (\Exception $e) {
                throw new AsyncPaymentFinalizeException(
                    $transactionId,
                    \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
                );
            }
        }

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
                throw new AsyncPaymentFinalizeException(
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
                throw new AsyncPaymentFinalizeException(
                    $transactionId,
                    \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
                );
            }
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

        if ($paymentState === PaymentStatusV1::PAYMENT_DENIED) {
            $this->orderTransactionStateHandler->fail($transactionId, $context);
        }

        $this->updateTransaction($response, $transactionId, $context);
    }

    /**
     * @param Patch[] $patches
     *
     * @throws AsyncPaymentProcessException
     */
    private function patchPayPalPayment(
        array $patches,
        string $paypalPaymentId,
        string $salesChannelId,
        string $orderTransactionId
    ): void {
        try {
            $this->paymentResource->patch($patches, $paypalPaymentId, $salesChannelId);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }
    }

    private function addPayPalTransactionId(
        AsyncPaymentTransactionStruct $transaction,
        string $paypalPaymentId,
        Context $context,
        ?string $paypalToken = null
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
        $relatedResource = $payment->getTransactions()[0]->getRelatedResources()[0];
        $paymentState = '';

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
                $resource = $payment->getTransactions()[0]->getRelatedResources()[0]->getOrder();

                break;
            case PaymentIntentV1::AUTHORIZE:
                $resource = $payment->getTransactions()[0]->getRelatedResources()[0]->getAuthorization();

                break;
            case PaymentIntentV1::SALE:
                $resource = $payment->getTransactions()[0]->getRelatedResources()[0]->getSale();

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

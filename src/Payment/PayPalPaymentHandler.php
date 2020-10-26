<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment;

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
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Exception\PayPalApiException;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Handler\PlusHandler;
use Swag\PayPal\Payment\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';
    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';
    public const PAYPAL_EXPRESS_CHECKOUT_ID = 'isPayPalExpressCheckout';
    public const PAYPAL_SMART_PAYMENT_BUTTONS_ID = 'isPayPalSpbCheckout';
    public const PAYPAL_PLUS_CHECKOUT_ID = 'isPayPalPlusCheckout';
    public const PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER = 'isPayPalPlus';

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

    /**
     * @var EcsSpbHandler
     */
    private $ecsSpbHandler;

    /**
     * @var PayPalHandler
     */
    private $payPalHandler;

    /**
     * @var PlusHandler
     */
    private $plusHandler;

    /**
     * @var OrderNumberPatchBuilder
     */
    private $orderNumberPatchBuilder;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentResource $paymentResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EntityRepositoryInterface $orderTransactionRepo,
        EcsSpbHandler $ecsSpbHandler,
        PayPalHandler $payPalHandler,
        PlusHandler $plusHandler,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        SettingsServiceInterface $settingsService,
        LoggerInterface $logger
    ) {
        $this->paymentResource = $paymentResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderTransactionRepo = $orderTransactionRepo;
        $this->ecsSpbHandler = $ecsSpbHandler;
        $this->payPalHandler = $payPalHandler;
        $this->plusHandler = $plusHandler;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->settingsService = $settingsService;
        $this->logger = $logger;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new AsyncPaymentProcessException(
                $transactionId,
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());

        if ($dataBag->get(self::PAYPAL_EXPRESS_CHECKOUT_ID)) {
            try {
                return $this->ecsSpbHandler->handleEcsPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (\Exception $e) {
                throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
            }
        }

        if ($dataBag->get(self::PAYPAL_SMART_PAYMENT_BUTTONS_ID)) {
            return $this->ecsSpbHandler->handleSpbPayment($transaction, $dataBag, $salesChannelContext);
        }

        if ($dataBag->getBoolean(self::PAYPAL_PLUS_CHECKOUT_ID)) {
            try {
                return $this->plusHandler->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
            } catch (\Exception $e) {
                throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
            }
        }

        try {
            $response = $this->payPalHandler->handlePayPalPayment($transaction, $salesChannelContext, $customer);
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
        $transactionId = $transaction->getOrderTransaction()->getId();

        if ($request->query->getBoolean('cancel')) {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $settings = $this->settingsService->getSettings($salesChannelId);
        $payerId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
        $paymentId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        $isExpressCheckout = $request->query->getBoolean(self::PAYPAL_EXPRESS_CHECKOUT_ID);
        $isSPBCheckout = $request->query->getBoolean(self::PAYPAL_SMART_PAYMENT_BUTTONS_ID);
        $isPlus = $request->query->getBoolean(self::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER);
        $partnerAttributionId = $this->getPartnerAttributionId($isExpressCheckout, $isSPBCheckout, $isPlus);
        $orderNumber = $transaction->getOrder()->getOrderNumber();

        if ($settings->getSendOrderNumber()
            && $orderNumber !== null
            && ($isExpressCheckout || $isSPBCheckout || $isPlus)
        ) {
            $orderNumberPrefix = (string) $settings->getOrderNumberPrefix();
            $orderNumber = $orderNumberPrefix . $orderNumber;

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
                    'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
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
                throw $e;
            }

            $this->logger->warning($e->getMessage());

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
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }

        $paymentState = $this->getPaymentState($response);
        $context = $salesChannelContext->getContext();

        // apply the payment status if its completed by PayPal
        if ($paymentState === PaymentStatus::PAYMENT_COMPLETED) {
            $this->orderTransactionStateHandler->paid($transactionId, $context);
        }

        if ($paymentState === PaymentStatus::PAYMENT_DENIED) {
            $this->orderTransactionStateHandler->fail($transactionId, $context);
        }

        $this->savePaymentInstructions($response, $transactionId, $context);
    }

    private function getPartnerAttributionId(bool $isECS, bool $isSPB, bool $isPlus): string
    {
        if ($isECS) {
            return PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT;
        }

        if ($isSPB) {
            return PartnerAttributionId::SMART_PAYMENT_BUTTONS;
        }

        if ($isPlus) {
            return PartnerAttributionId::PAYPAL_PLUS;
        }

        return PartnerAttributionId::PAYPAL_CLASSIC;
    }

    private function getPaymentState(Payment $payment): string
    {
        $intent = $payment->getIntent();
        $relatedResource = $payment->getTransactions()[0]->getRelatedResources()[0];
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

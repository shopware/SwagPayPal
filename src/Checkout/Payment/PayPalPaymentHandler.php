<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RecurringPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PayPalPaymentHandler implements AsynchronousPaymentHandlerInterface, RecurringPaymentHandlerInterface
{
    public const PAYPAL_REQUEST_PARAMETER_CANCEL = 'cancel';
    public const PAYPAL_REQUEST_PARAMETER_PAYER_ID = 'PayerID';
    public const PAYPAL_REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';
    public const PAYPAL_REQUEST_PARAMETER_TOKEN = 'token';
    public const PAYPAL_EXPRESS_CHECKOUT_ID = 'isPayPalExpressCheckout';
    public const PAYPAL_SMART_PAYMENT_BUTTONS_ID = 'isPayPalSpbCheckout';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER = 'isPayPalPlus';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const PAYPAL_PLUS_CHECKOUT_ID = 'isPayPalPlusCheckout';

    public const FINALIZED_ORDER_TRANSACTION_STATES = [
        OrderTransactionStates::STATE_PAID,
        OrderTransactionStates::STATE_AUTHORIZED,
    ];

    /**
     * @internal
     */
    public function __construct(
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly PayPalHandler $payPalHandler,
        private readonly PlusPuiHandler $plusPuiHandler,
        private readonly EntityRepository $stateMachineStateRepository,
        private readonly LoggerInterface $logger,
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly VaultTokenService $vaultTokenService,
        private readonly OrderConverter $orderConverter,
    ) {
    }

    /**
     * @throws PaymentException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();

        try {
            $customer = $salesChannelContext->getCustomer();
            if ($customer === null) {
                throw CartException::customerNotLoggedIn();
            }

            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
            $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());

            if ($dataBag->get(self::PAYPAL_EXPRESS_CHECKOUT_ID) || $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)) {
                return $this->payPalHandler->handlePreparedOrder($transaction, $dataBag, $salesChannelContext);
            }

            if ($dataBag->getBoolean(self::PAYPAL_PLUS_CHECKOUT_ID)) {
                return $this->plusPuiHandler->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
            }

            return $this->payPalHandler->handlePayPalOrder($transaction, $dataBag, $salesChannelContext);
        } catch (PaymentException $e) {
            if ($e->getParameter('orderTransactionId') === null && method_exists($e, 'setOrderTransactionId')) {
                $e->setOrderTransactionId($transactionId);
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw PaymentException::asyncProcessInterrupted($transactionId, $e->getMessage());
        }
    }

    /**
     * @throws PaymentException
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext,
    ): void {
        $this->logger->debug('Started');

        if ($this->transactionAlreadyFinalized($transaction, $salesChannelContext)) {
            $this->logger->debug('Already finalized');

            return;
        }

        if ($request->query->getBoolean(self::PAYPAL_REQUEST_PARAMETER_CANCEL)) {
            $this->logger->debug('Customer canceled');

            throw PaymentException::customerCanceled(
                $transaction->getOrderTransaction()->getId(),
                'Customer canceled the payment on the PayPal page'
            );
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());

            $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
            $context = $salesChannelContext->getContext();

            $paymentId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);

            $isExpressCheckout = $request->query->getBoolean(self::PAYPAL_EXPRESS_CHECKOUT_ID);
            $isSPBCheckout = $request->query->getBoolean(self::PAYPAL_SMART_PAYMENT_BUTTONS_ID);
            $isPlus = $request->query->getBoolean(self::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER);

            $partnerAttributionId = $this->getPartnerAttributionId($isExpressCheckout, $isSPBCheckout, $isPlus);

            if (\is_string($paymentId)) {
                $payerId = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
                if (!\is_string($payerId)) {
                    throw RoutingException::missingRequestParameter(self::PAYPAL_REQUEST_PARAMETER_PAYER_ID);
                }

                $this->plusPuiHandler->handleFinalizePayment(
                    $transaction,
                    $salesChannelId,
                    $context,
                    $paymentId,
                    $payerId,
                    $partnerAttributionId
                );

                return;
            }

            $token = $request->query->get(self::PAYPAL_REQUEST_PARAMETER_TOKEN);
            if (!\is_string($token)) {
                throw RoutingException::missingRequestParameter(self::PAYPAL_REQUEST_PARAMETER_TOKEN);
            }

            $this->payPalHandler->handleFinalizeOrder(
                $transaction,
                $token,
                $salesChannelId,
                $salesChannelContext,
                $partnerAttributionId
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $e->getMessage());
        }
    }

    public function captureRecurring(RecurringPaymentTransactionStruct $transaction, Context $context): void
    {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();

        $subscription = $this->vaultTokenService->getSubscription($transaction);
        if (!$subscription) {
            throw PaymentException::recurringInterrupted($transactionId, 'Subscription not found');
        }

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($transaction->getOrder(), $context);

        try {
            $this->settingsValidationService->validate($subscription->getSalesChannelId());
            $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $context);

            $redirect = $this->payPalHandler->handlePayPalOrder($transaction, new RequestDataBag(), $salesChannelContext);
            $this->payPalHandler->handleFinalizeOrder($transaction, $redirect->getTargetUrl(), $subscription->getSalesChannelId(), $salesChannelContext, PartnerAttributionId::PAYPAL_PPCP);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw PaymentException::recurringInterrupted($transactionId, $e->getMessage());
        }
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

    private function transactionAlreadyFinalized(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext,
    ): bool {
        $transactionStateMachineStateId = $transaction->getOrderTransaction()->getStateId();
        $criteria = new Criteria([$transactionStateMachineStateId]);

        /** @var StateMachineStateEntity|null $stateMachineState */
        $stateMachineState = $this->stateMachineStateRepository->search(
            $criteria,
            $salesChannelContext->getContext()
        )->get($transactionStateMachineStateId);

        if ($stateMachineState === null) {
            return false;
        }

        return \in_array(
            $stateMachineState->getTechnicalName(),
            self::FINALIZED_ORDER_TRANSACTION_STATES,
            true
        );
    }
}

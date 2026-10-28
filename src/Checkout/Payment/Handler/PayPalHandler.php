<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayPalHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PayPalOrderBuilder $orderBuilder,
        private readonly OrderResource $orderResource,
        private readonly OrderExecuteService $orderExecuteService,
        private readonly OrderPatchService $orderPatchService,
        private readonly TransactionDataService $transactionDataService,
        private readonly VaultTokenService $vaultTokenService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PaymentException
     */
    public function handlePayPalOrder(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $requestDataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        $this->logger->debug('Started');

        $paypalOrder = $this->orderBuilder->getOrder(
            $transaction,
            $salesChannelContext,
            $requestDataBag,
        );

        $transactionId = $transaction->getOrderTransaction()->getId();
        $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();

        try {
            $paypalOrderResponse = $this->orderResource->create(
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_CLASSIC,
                false,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
            );
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                || !$e->is(PayPalApiException::ISSUE_DUPLICATE_INVOICE_ID)) {
                throw $e;
            }

            $this->logger->warning('Duplicate order number detected. Retrying payment without order number.');
            $paypalOrder->getPurchaseUnits()->first()?->unset('invoiceId');

            $paypalOrderResponse = $this->orderResource->create(
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_CLASSIC,
                false,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() + 1 : '1'),
            );
        }

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderResponse->getId(),
            PartnerAttributionId::PAYPAL_CLASSIC,
            $salesChannelContext
        );

        if ($paypalOrderResponse->getStatus() !== PaymentStatusV2::ORDER_PAYER_ACTION_REQUIRED
         && $paypalOrderResponse->getStatus() !== PaymentStatusV2::ORDER_CREATED) {
            if (!$transaction instanceof AsyncPaymentTransactionStruct) {
                return new RedirectResponse($paypalOrderResponse->getId());
            }

            $parameters = \http_build_query([
                PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderResponse->getId(),
            ]);

            return new RedirectResponse(\sprintf('%s&%s', $transaction->getReturnUrl(), $parameters));
        }

        $link = $paypalOrderResponse->getLinks()->getRelation(Link::RELATION_APPROVE)
            ?? $paypalOrderResponse->getLinks()->getRelation(Link::RELATION_PAYER_ACTION);
        if ($link === null) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'No approve link provided by PayPal');
        }

        return new RedirectResponse($link->getHref());
    }

    /**
     * @throws PaymentException
     */
    public function handlePreparedOrder(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalOrderId = $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $isECS = $dataBag->get(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID);
        $partnerAttributionId = $isECS ? PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT : PartnerAttributionId::SMART_PAYMENT_BUTTONS;

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderId,
            $partnerAttributionId,
            $salesChannelContext
        );

        $this->orderPatchService->patchOrder(
            $transaction->getOrder(),
            $transaction->getOrderTransaction(),
            $salesChannelContext,
            $paypalOrderId,
            $partnerAttributionId
        );

        $parameters = \http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderId,
            $isECS ? PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID : PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => true,
        ]);
        $finalizeUrl = \sprintf('%s&%s', $transaction->getReturnUrl(), $parameters);

        // captured here instead of in finalize, which cannot send the payer back to PayPal
        try {
            $this->handleFinalizeOrder(
                $transaction,
                $paypalOrderId,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext,
                $partnerAttributionId
            );
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                || !$e->is(PayPalApiException::ISSUE_PAYER_ACTION_REQUIRED)) {
                throw $e;
            }

            return $this->recoverFromPayerAction($e, $transaction, $dataBag, $salesChannelContext, $paypalOrderId, $partnerAttributionId, $finalizeUrl);
        }

        // finalize finds the transaction already paid or authorized
        return new RedirectResponse($finalizeUrl);
    }

    /**
     * @throws PaymentException
     */
    public function handleFinalizeOrder(
        SyncPaymentTransactionStruct $transaction,
        string $paypalOrderId,
        string $salesChannelId,
        SalesChannelContext $context,
        string $partnerAttributionId,
    ): void {
        $this->logger->debug('Started');

        $paypalOrder = $this->orderExecuteService->captureOrAuthorizeOrder(
            $transaction->getOrderTransaction()->getId(),
            $this->orderResource->get($paypalOrderId, $salesChannelId),
            $salesChannelId,
            $context->getContext(),
            $partnerAttributionId
        );

        $this->transactionDataService->setResourceId(
            $paypalOrder,
            $transaction->getOrderTransaction()->getId(),
            $context->getContext()
        );

        if (!($paymentSource = $paypalOrder->getPaymentSource()?->getPaypal())) {
            throw PaymentException::asyncFinalizeInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'Missing payment details for PayPal payment source'
            );
        }

        $customerId = $context->getCustomerId();
        if (!$customerId) {
            return;
        }

        $this->vaultTokenService->saveToken($transaction, $paymentSource, $customerId, $context->getContext());
    }

    /**
     * Reopens the approval of a prepared order PayPal refused to capture, e.g. because its amount rose
     * after the payer approved it, and redirects the payer to approve it again and return into finalize.
     *
     * @see https://developer.paypal.com/docs/checkout/standard/customize/overcharge-handling/
     */
    private function recoverFromPayerAction(
        PayPalApiException $exception,
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        string $paypalOrderId,
        string $partnerAttributionId,
        string $finalizeUrl,
    ): RedirectResponse {
        $this->logger->warning('PayPal requires another payer action before the order can be captured.', [
            'orderTransactionId' => $transaction->getOrderTransaction()->getId(),
            'payPalOrderId' => $paypalOrderId,
        ]);

        // without a saved vault token, but the payer still gets the vaulting they asked for
        $confirmationDataBag = new RequestDataBag([AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true]);
        if ($dataBag->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT)) {
            $confirmationDataBag->set(VaultTokenService::REQUEST_CREATE_VAULT, true);
        }

        $paymentSource = $this->orderBuilder
            ->getOrder($transaction, $salesChannelContext, $confirmationDataBag)
            ->getPaymentSource();
        $paypal = $paymentSource?->getPaypal();
        if ($paymentSource === null || $paypal === null || !$paypal->isset('experienceContext')) {
            throw $exception;
        }

        $experienceContext = $paypal->getExperienceContext();
        // approved with PAY_NOW, the default, PayPal still rejects the capture with `PAYER_ACTION_REQUIRED`
        $experienceContext->setUserAction(ExperienceContext::USER_ACTION_CONTINUE);
        // finalize needs the token and the Express or Smart Payment Buttons flag
        $experienceContext->setReturnUrl($finalizeUrl);

        try {
            $confirmedOrder = $this->orderResource->confirm(
                $paypalOrderId,
                $paymentSource,
                $salesChannelContext->getSalesChannelId(),
                $partnerAttributionId,
            );
        } catch (PayPalApiException $confirmationException) {
            $this->logger->warning('Could not reopen the PayPal order for payer approval.', [
                'orderTransactionId' => $transaction->getOrderTransaction()->getId(),
                'payPalOrderId' => $paypalOrderId,
                'exception' => $confirmationException,
            ]);

            throw $exception;
        }

        // Order::$links has no default and PayPal may omit it
        $link = $confirmedOrder->isset('links') ? $confirmedOrder->getLinks()->getRelation(Link::RELATION_PAYER_ACTION) : null;
        if ($link === null) {
            throw $exception;
        }

        return new RedirectResponse($link->getHref());
    }
}

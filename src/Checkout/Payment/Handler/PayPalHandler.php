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
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
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

    public function handlePreparedOrder(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalOrderId = $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $isECS = $dataBag->get(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID);

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderId,
            $isECS ? PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT : PartnerAttributionId::SMART_PAYMENT_BUTTONS,
            $salesChannelContext
        );

        $this->orderPatchService->patchOrder(
            $transaction->getOrder(),
            $transaction->getOrderTransaction(),
            $salesChannelContext,
            $paypalOrderId,
            $isECS ? PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT : PartnerAttributionId::SMART_PAYMENT_BUTTONS
        );

        $parameters = \http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderId,
            $isECS ? PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID : PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => true,
        ]);

        return new RedirectResponse(\sprintf('%s&%s', $transaction->getReturnUrl(), $parameters));
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
}

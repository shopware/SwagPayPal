<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RecurringPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Card\CardValidatorInterface;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class ACDCHandler extends AbstractPaymentMethodHandler implements AsynchronousPaymentHandlerInterface, RecurringPaymentHandlerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly OrderExecuteService $orderExecuteService,
        private readonly OrderPatchService $orderPatchService,
        private readonly TransactionDataService $transactionDataService,
        private readonly LoggerInterface $logger,
        private readonly OrderResource $orderResource,
        private readonly CardValidatorInterface $acdcValidator,
        private readonly VaultTokenService $vaultTokenService,
        private readonly ACDCOrderBuilder $orderBuilder,
        private readonly OrderConverter $orderConverter,
    ) {
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $existingVault = $this->vaultTokenService->getAvailableToken($transaction, $salesChannelContext->getContext());

        if (!$paypalOrderId && !$existingVault) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'Missing PayPal order id');
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());

            $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());

            $response = null;
            if (!$paypalOrderId) {
                $paypalOrder = $this->orderBuilder->getOrder($transaction, $salesChannelContext, $dataBag);
                $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();
                $response = $this->orderResource->create(
                    $paypalOrder,
                    $salesChannelContext->getSalesChannelId(),
                    PartnerAttributionId::PAYPAL_PPCP,
                    true,
                    $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
                );
                $paypalOrderId = $response->getId();
            }

            $this->transactionDataService->setOrderId(
                $transactionId,
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );

            if (!$response) {
                $this->orderPatchService->patchOrder(
                    $transaction->getOrder(),
                    $transaction->getOrderTransaction(),
                    $salesChannelContext,
                    $paypalOrderId,
                    PartnerAttributionId::PAYPAL_PPCP
                );
            }

            $action = $response?->getLinks()->getRelation(Link::RELATION_PAYER_ACTION)?->getHref();

            return new RedirectResponse($action ?? $transaction->getReturnUrl());
        } catch (PaymentException $e) {
            if ($e->getParameter('orderTransactionId') === null && method_exists($e, 'setOrderTransactionId')) {
                $e->setOrderTransactionId($transactionId);
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::asyncProcessInterrupted($transactionId, $e->getMessage());
        }
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $transaction->getOrderTransaction()->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);
        if (!\is_string($paypalOrderId)) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'Missing PayPal order id');
        }

        try {
            $paypalOrder = $this->executeOrder(
                $transaction,
                $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId()),
                $salesChannelContext
            );

            $this->transactionDataService->setResourceId($paypalOrder, $transactionId, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::asyncProcessInterrupted($transactionId, $e->getMessage());
        }

        $card = $paypalOrder->getPaymentSource()?->getCard();
        if (!$card) {
            return;
        }

        $customerId = $salesChannelContext->getCustomerId();
        if (!$customerId) {
            return;
        }

        $this->vaultTokenService->saveToken($transaction, $card, $customerId, $salesChannelContext->getContext());
    }

    public function captureRecurring(RecurringPaymentTransactionStruct $transaction, Context $context): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $subscription = $this->vaultTokenService->getSubscription($transaction);
        if (!$subscription) {
            throw PaymentException::recurringInterrupted($transactionId, 'Subscription not found');
        }

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($transaction->getOrder(), $context);

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
            $paypalOrder = $this->orderBuilder->getOrder($transaction, $salesChannelContext, new RequestDataBag());
            $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();
            $response = $this->orderResource->create(
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_PPCP,
                true,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
            );

            $this->transactionDataService->setOrderId(
                $transactionId,
                $response->getId(),
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );
            $this->transactionDataService->setResourceId($response, $transactionId, $salesChannelContext->getContext());
            $this->orderExecuteService->captureOrAuthorizeOrder(
                $transactionId,
                $response,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::recurringInterrupted($transactionId, $e->getMessage());
        }
    }

    protected function executeOrder(SyncPaymentTransactionStruct $transaction, Order $paypalOrder, SalesChannelContext $salesChannelContext): Order
    {
        // fallback button
        $paymentSource = $paypalOrder->getPaymentSource();
        if ($paymentSource === null) {
            throw new MissingPayloadException($paypalOrder->getId(), 'paymentSource');
        }

        if ($paymentSource->getPaypal() !== null && $paymentSource->getCard() === null) {
            return $this->orderExecuteService->captureOrAuthorizeOrder(
                $transaction->getOrderTransaction()->getId(),
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP,
            );
        }

        if (!$this->acdcValidator->validate($paypalOrder, $transaction, $salesChannelContext)) {
            throw CardValidationFailedException::cardValidationFailed($transaction->getOrderTransaction()->getId());
        }

        return $this->orderExecuteService->captureOrAuthorizeOrder(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrder,
            $salesChannelContext->getSalesChannelId(),
            $salesChannelContext->getContext(),
            PartnerAttributionId::PAYPAL_PPCP,
        );
    }
}

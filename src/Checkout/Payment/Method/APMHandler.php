<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class APMHandler extends AbstractPaymentMethodHandler implements AsynchronousPaymentHandlerInterface
{
    private TransactionDataService $transactionDataService;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private SettingsValidationServiceInterface $settingsValidationService;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    private AbstractAPMOrderBuilder $orderBuilder;

    /**
     * @internal
     */
    public function __construct(
        TransactionDataService $transactionDataService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        SettingsValidationServiceInterface $settingsValidationService,
        OrderResource $orderResource,
        LoggerInterface $logger,
        AbstractAPMOrderBuilder $orderBuilder,
    ) {
        $this->transactionDataService = $transactionDataService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->settingsValidationService = $settingsValidationService;
        $this->orderResource = $orderResource;
        $this->logger = $logger;
        $this->orderBuilder = $orderBuilder;
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $this->logger->debug('Started');
        $transactionId = $transaction->getOrderTransaction()->getId();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $exception) {
            throw PaymentException::asyncProcessInterrupted($transactionId, $exception->getMessage());
        }

        $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());

        $this->logger->debug('Building order');

        $paypalOrder = $this->orderBuilder->getOrder(
            $transaction,
            $salesChannelContext,
            $dataBag
        );

        try {
            $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();

            $response = $this->orderResource->create(
                $paypalOrder,
                $salesChannelId,
                PartnerAttributionId::PAYPAL_PPCP,
                true,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
            );

            $this->logger->debug('Created order');
        } catch (PaymentException $e) {
            if ($e->getParameter('orderTransactionId') === null && method_exists($e, 'setOrderTransactionId')) {
                $e->setOrderTransactionId($transactionId);
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::asyncProcessInterrupted(
                $transactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->transactionDataService->setOrderId(
            $transactionId,
            $response->getId(),
            PartnerAttributionId::PAYPAL_PPCP,
            $salesChannelContext
        );

        $link = $response->getLinks()->getRelation(Link::RELATION_PAYER_ACTION);
        if ($link === null) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'No approve link provided by PayPal');
        }

        return new RedirectResponse($link->getHref());
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $transaction->getOrderTransaction()->getCustomFields()[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID] ?? null;
        if (!$paypalOrderId) {
            throw PaymentException::invalidTransaction($transactionId);
        }

        if ($request->query->getBoolean(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL)) {
            $this->logger->debug('Customer canceled');

            throw PaymentException::customerCanceled(
                $transaction->getOrderTransaction()->getId(),
                'Customer canceled the payment on the PayPal page'
            );
        }

        try {
            $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId());
            $this->tryToSetTransactionState($paypalOrder, $transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
        } catch (PaymentException $e) {
            if ($e->getParameter('orderTransactionId') === null && method_exists($e, 'setOrderTransactionId')) {
                $e->setOrderTransactionId($transactionId);
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::asyncProcessInterrupted($transactionId, $e->getMessage());
        }

        try {
            $this->transactionDataService->setResourceId($paypalOrder, $transactionId, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            $this->logger->warning('Could not set resource id: ' . $e->getMessage());
        }
    }

    private function tryToSetTransactionState(Order $paypalOrder, string $transactionId, Context $context): void
    {
        $purchaseUnits = $paypalOrder->getPurchaseUnits();
        if ($purchaseUnits->count()) {
            return;
        }
        $payments = $purchaseUnits->first()?->getPayments();
        if ($payments === null) {
            return;
        }
        $capture = $payments->getCaptures()?->first();
        if ($capture === null) {
            return;
        }

        if ($capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_COMPLETED) {
            $this->orderTransactionStateHandler->paid($transactionId, $context);
        }

        if ($capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_DECLINED
            || $capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_FAILED
        ) {
            throw new OrderFailedException($paypalOrder->getId());
        }
    }
}

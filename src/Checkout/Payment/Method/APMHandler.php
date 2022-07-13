<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class APMHandler extends AbstractPaymentMethodHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderExecuteService $orderExecuteService;

    private TransactionDataService $transactionDataService;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private SettingsValidationServiceInterface $settingsValidationService;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    private AbstractAPMOrderBuilder $orderBuilder;

    public function __construct(
        OrderExecuteService $orderExecuteService,
        TransactionDataService $transactionDataService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        SettingsValidationServiceInterface $settingsValidationService,
        OrderResource $orderResource,
        LoggerInterface $logger,
        AbstractAPMOrderBuilder $orderBuilder
    ) {
        $this->orderExecuteService = $orderExecuteService;
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
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            $message = (new CustomerNotLoggedInException())->getMessage();
            $this->logger->error($message);

            throw new AsyncPaymentProcessException($transactionId, $message);
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

        $this->logger->debug('Building order');

        $paypalOrder = $this->orderBuilder->getOrder(
            $transaction,
            $salesChannelContext,
            $customer,
            $dataBag
        );

        try {
            $response = $this->orderResource->create(
                $paypalOrder,
                $salesChannelId,
                PartnerAttributionId::PAYPAL_PPCP,
                true,
                Uuid::randomHex()
            );

            $this->logger->debug('Created order');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw new AsyncPaymentProcessException(
                $transactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->transactionDataService->setOrderId(
            $transactionId,
            $response->getId(),
            PartnerAttributionId::PAYPAL_PPCP,
            $salesChannelContext->getContext()
        );

        $link = $response->getRelLink(Link::RELATION_PAYER_ACTION);
        if ($link === null) {
            throw new AsyncPaymentProcessException($transactionId, 'No approve link provided by PayPal');
        }

        return new RedirectResponse($link->getHref());
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $transaction->getOrderTransaction()->getCustomFields()[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID] ?? null;
        if (!$paypalOrderId) {
            throw new InvalidTransactionException($transactionId);
        }

        if ($this->orderBuilder->isCompleteOnApproval()) {
            // order will be automatically captured, state should be received via webhook
            return;
        }

        try {
            $paypalOrder = $this->orderExecuteService->captureOrAuthorizeOrder(
                $transactionId,
                $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId()),
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP,
            );

            $this->transactionDataService->setResourceId($paypalOrder, $transactionId, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw new AsyncPaymentProcessException($transactionId, $e->getMessage());
        }
    }
}

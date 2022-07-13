<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

abstract class AbstractSyncAPMHandler extends AbstractPaymentMethodHandler implements SynchronousPaymentHandlerInterface
{
    private OrderExecuteService $orderExecuteService;

    private OrderPatchService $orderPatchService;

    private TransactionDataService $transactionDataService;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private SettingsValidationServiceInterface $settingsValidationService;

    private LoggerInterface $logger;

    private ?OrderResource $orderResource;

    /**
     * @deprecated tag:v6.0.0 - orderResource will be required
     */
    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        LoggerInterface $logger,
        ?OrderResource $orderResource = null
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderPatchService = $orderPatchService;
        $this->orderExecuteService = $orderExecuteService;
        $this->transactionDataService = $transactionDataService;
        $this->logger = $logger;
        $this->orderResource = $orderResource;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);

        if (!$paypalOrderId) {
            throw new SyncPaymentProcessException($transactionId, 'Missing PayPal order id');
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());

            if (\method_exists($this->orderTransactionStateHandler, 'processUnconfirmed')) {
                $this->orderTransactionStateHandler->processUnconfirmed($transactionId, $salesChannelContext->getContext());
            } else {
                $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());
            }

            $this->transactionDataService->setOrderId(
                $transactionId,
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext->getContext()
            );

            $this->orderPatchService->patchOrderData(
                $transaction->getOrderTransaction()->getId(),
                $transaction->getOrder()->getOrderNumber(),
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext->getSalesChannelId()
            );

            if ($this->orderResource === null) {
                throw new \RuntimeException('orderResource is required');
            }

            $paypalOrder = $this->executeOrder(
                $transaction,
                $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId()),
                $salesChannelContext
            );

            $this->transactionDataService->setResourceId($paypalOrder, $transactionId, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw new SyncPaymentProcessException($transactionId, $e->getMessage());
        }
    }

    protected function executeOrder(SyncPaymentTransactionStruct $transaction, Order $paypalOrder, SalesChannelContext $salesChannelContext): Order
    {
        return $this->orderExecuteService->captureOrAuthorizeOrder(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrder,
            $salesChannelContext->getSalesChannelId(),
            $salesChannelContext->getContext(),
            PartnerAttributionId::PAYPAL_PPCP,
        );
    }
}

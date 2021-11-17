<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;

class PayPalHandler extends AbstractPaymentHandler
{
    private OrderFromOrderBuilder $orderBuilder;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    private OrderExecuteService $orderExecuteService;

    private OrderPatchService $orderPatchService;

    private TransactionDataService $transactionDataService;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        OrderFromOrderBuilder $orderBuilder,
        OrderResource $orderResource,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        LoggerInterface $logger
    ) {
        parent::__construct($orderTransactionRepo);
        $this->orderBuilder = $orderBuilder;
        $this->orderResource = $orderResource;
        $this->orderExecuteService = $orderExecuteService;
        $this->orderPatchService = $orderPatchService;
        $this->transactionDataService = $transactionDataService;
        $this->logger = $logger;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function handlePayPalOrder(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): PayPalOrder {
        $this->logger->debug('Started');
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $orderTransactionId = $transaction->getOrderTransaction()->getId();

        $paypalOrder = $this->orderBuilder->getOrder(
            $transaction,
            $salesChannelContext,
            $customer
        );

        try {
            $paypalOrderResponse = $this->orderResource->create(
                $paypalOrder,
                $salesChannelId,
                PartnerAttributionId::PAYPAL_CLASSIC
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->transactionDataService->setOrderId(
            $orderTransactionId,
            $paypalOrderResponse->getId(),
            PartnerAttributionId::PAYPAL_CLASSIC,
            $salesChannelContext->getContext()
        );

        return $paypalOrderResponse;
    }

    /**
     * @throws AsyncPaymentFinalizeException
     */
    public function handleFinalizeOrder(
        AsyncPaymentTransactionStruct $transaction,
        string $paypalOrderId,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId,
        bool $orderDataPatchNeeded
    ): void {
        $this->logger->debug('Started');

        try {
            if ($orderDataPatchNeeded) {
                $this->orderPatchService->patchOrderData(
                    $transaction->getOrderTransaction()->getId(),
                    $transaction->getOrder()->getOrderNumber(),
                    $paypalOrderId,
                    $partnerAttributionId,
                    $salesChannelId
                );
            }

            $paypalOrder = $this->orderExecuteService->executeOrder(
                $transaction->getOrderTransaction()->getId(),
                $paypalOrderId,
                $salesChannelId,
                $context,
                $partnerAttributionId
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->transactionDataService->setResourceId(
            $paypalOrder,
            $transaction->getOrderTransaction()->getId(),
            $context
        );
    }
}

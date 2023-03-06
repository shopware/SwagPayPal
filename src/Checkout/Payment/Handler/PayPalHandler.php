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
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PayPalHandler
{
    private OrderFromOrderBuilder $orderBuilder;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    private OrderExecuteService $orderExecuteService;

    private OrderPatchService $orderPatchService;

    private TransactionDataService $transactionDataService;

    /**
     * @internal
     */
    public function __construct(
        OrderFromOrderBuilder $orderBuilder,
        OrderResource $orderResource,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        LoggerInterface $logger
    ) {
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

        $paypalOrder = $this->orderBuilder->getOrder(
            $transaction,
            $salesChannelContext,
            $customer
        );

        try {
            $paypalOrderResponse = $this->orderResource->create(
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_CLASSIC
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderResponse->getId(),
            PartnerAttributionId::PAYPAL_CLASSIC,
            $salesChannelContext->getContext()
        );

        return $paypalOrderResponse;
    }

    public function handlePreparedOrder(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalOrderId = $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $isECS = $dataBag->get(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID);

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderId,
            $isECS ? PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT : PartnerAttributionId::SMART_PAYMENT_BUTTONS,
            $salesChannelContext->getContext()
        );

        try {
            $this->orderPatchService->patchOrder(
                $transaction->getOrder(),
                $transaction->getOrderTransaction(),
                $salesChannelContext,
                $paypalOrderId,
                $isECS ? PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT : PartnerAttributionId::SMART_PAYMENT_BUTTONS
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $parameters = \http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderId,
            $isECS ? PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID : PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => true,
        ]);

        return new RedirectResponse(\sprintf('%s&%s', $transaction->getReturnUrl(), $parameters));
    }

    /**
     * @throws AsyncPaymentFinalizeException
     */
    public function handleFinalizeOrder(
        AsyncPaymentTransactionStruct $transaction,
        string $paypalOrderId,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId
    ): void {
        $this->logger->debug('Started');

        try {
            $paypalOrder = $this->orderExecuteService->captureOrAuthorizeOrder(
                $transaction->getOrderTransaction()->getId(),
                $this->orderResource->get($paypalOrderId, $salesChannelId),
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

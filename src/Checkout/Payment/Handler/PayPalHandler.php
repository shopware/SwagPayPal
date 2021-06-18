<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\OrdersApi\Patch\CustomIdPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\Response;

class PayPalHandler extends AbstractPaymentHandler
{
    private OrderFromOrderBuilder $orderBuilder;

    private OrderResource $orderResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private SystemConfigService $systemConfigService;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private CustomIdPatchBuilder $customIdPatchBuilder;

    private LoggerInterface $logger;

    private StateMachineRegistry $stateMachineRegistry;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        OrderFromOrderBuilder $orderBuilder,
        OrderResource $orderResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        SystemConfigService $systemConfigService,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        CustomIdPatchBuilder $customIdPatchBuilder,
        StateMachineRegistry $stateMachineRegistry,
        LoggerInterface $logger
    ) {
        parent::__construct($orderTransactionRepo);
        $this->orderBuilder = $orderBuilder;
        $this->orderResource = $orderResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->systemConfigService = $systemConfigService;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->customIdPatchBuilder = $customIdPatchBuilder;
        $this->stateMachineRegistry = $stateMachineRegistry;
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

        $this->addPayPalOrderId(
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
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderNumber = $transaction->getOrder()->getOrderNumber();

        if ($orderDataPatchNeeded) {
            $patches = [$this->customIdPatchBuilder->createCustomIdPatch($transactionId)];

            if ($orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelId)) {
                $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelId);
                $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $salesChannelId);
                $orderNumber = $orderNumberPrefix . $orderNumber . $orderNumberSuffix;
                $patches[] = $this->orderNumberPatchBuilder->createOrderNumberPatch($orderNumber);
            }

            try {
                $this->orderResource->update(
                    $patches,
                    $paypalOrderId,
                    $salesChannelId,
                    $partnerAttributionId
                );
            } catch (\Exception $e) {
                throw new AsyncPaymentFinalizeException(
                    $transactionId,
                    \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
                );
            }
        }

        $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelId);

        try {
            if ($paypalOrder->getIntent() === PaymentIntentV2::CAPTURE) {
                $response = $this->orderResource->capture($paypalOrderId, $salesChannelId, $partnerAttributionId);
                if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                    $this->orderTransactionStateHandler->paid($transactionId, $context);
                }
            } else {
                $response = $this->orderResource->authorize($paypalOrderId, $salesChannelId, $partnerAttributionId);
                if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                    // ToDo: Replace after NEXT-13973 is in min-version
                    $this->setTransactionToAuthorize($transactionId, $context);
                }
            }
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                || (\mb_strpos($e->getMessage(), PayPalApiException::ERROR_CODE_DUPLICATE_INVOICE_ID) === false)) {
                throw $e;
            }

            $this->logger->warning('Duplicate order number {orderNumber} detected. Retrying payment without order number.', ['orderNumber' => $orderNumber]);

            $this->orderResource->update(
                [$this->orderNumberPatchBuilder->createRemoveOrderNumberPatch()],
                $paypalOrderId,
                $salesChannelId,
                $partnerAttributionId
            );

            if ($paypalOrder->getIntent() === PaymentIntentV2::CAPTURE) {
                $response = $this->orderResource->capture($paypalOrderId, $salesChannelId, $partnerAttributionId);
                if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                    $this->orderTransactionStateHandler->paid($transactionId, $context);
                }
            } else {
                $response = $this->orderResource->authorize($paypalOrderId, $salesChannelId, $partnerAttributionId);
                if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                    // ToDo: Replace after NEXT-13973 is in min-version
                    $this->setTransactionToAuthorize($transactionId, $context);
                }
            }
        } catch (\Exception $e) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $this->addPayPalResourceId($response, $transactionId, $context);
    }

    private function addPayPalResourceId(PayPalOrder $order, string $transactionId, Context $context): void
    {
        $payments = $order->getPurchaseUnits()[0]->getPayments();

        $id = null;
        if ($order->getIntent() === PaymentIntentV2::CAPTURE && $captures = $payments->getCaptures()) {
            $id = $captures[0]->getId();
        } elseif ($order->getIntent() === PaymentIntentV2::AUTHORIZE && $authorizations = $payments->getAuthorizations()) {
            $id = $authorizations[0]->getId();
        }

        if ($id === null) {
            return;
        }

        $this->orderTransactionRepo->update([[
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => $id,
            ],
        ]], $context);
    }

    /**
     * ToDo: Replace after NEXT-13973 is in min-Version
     */
    private function setTransactionToAuthorize(string $transactionId, Context $context): void
    {
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transactionId,
                StateMachineTransitionActions::ACTION_AUTHORIZE,
                'stateId'
            ),
            $context
        );
    }
}

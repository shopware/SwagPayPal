<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\MessageQueue;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\StateMachineException;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler]
class TransactionStatusSyncMessageHandler
{
    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly OrderResource $orderResource,
        private readonly TransactionDataService $transactionDataService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(TransactionStatusSyncMessage $message): void
    {
        $context = Context::createDefaultContext();
        $logContext = [
            LogKey::SALES_CHANNEL_ID => $message->getSalesChannelId(),
            LogKey::ORDER_TRANSACTION_ID => $message->getTransactionId(),
            LogKey::PAYPAL_ORDER_ID => $message->getPayPalOrderId() ?: null,
        ];

        try {
            // Check if transaction is still unconfirmed at time of execution
            $criteria = (new Criteria([$message->getTransactionId()]))
                ->addAssociation('stateMachineState')
                ->addFilter(new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    [
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_UNCONFIRMED),
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_AUTHORIZED),
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                    ]
                ))
                // a new payment attempt since the message was queued has its own PayPal order
                ->addFilter(new EqualsFilter('customFields.' . SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID, $message->getPayPalOrderId()));

            /**
             * @var OrderTransactionEntity|null $transaction
             */
            $transaction = $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();
            if ($transaction === null) {
                return;
            }

            if (!$message->getPayPalOrderId()) {
                $this->orderTransactionStateHandler->cancel($message->getTransactionId(), $context);

                $this->logger->info('The status sync cancelled a payment that never got a PayPal order', [...$logContext, LogKey::CODE => LogCode::STATUS_SYNC_PAYPAL_ORDER_MISSING]);

                return;
            }

            $order = $this->orderResource->get($message->getPayPalOrderId(), $message->getSalesChannelId());

            // Set the resource ID for the transaction
            $this->transactionDataService->setResourceId($order, $message->getTransactionId(), $context);

            $currentState = $transaction->getStateMachineState()?->getTechnicalName();
            $isUnconfirmed = $currentState === OrderTransactionStates::STATE_UNCONFIRMED;
            $isAuthorized = $currentState !== OrderTransactionStates::STATE_AUTHORIZED;

            if ($order->getIntent() === ConstantsV2::INTENT_CAPTURE) {
                match ($order->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->first()?->getStatus()) {
                    ConstantsV2::ORDER_CAPTURE_COMPLETED => $this->orderTransactionStateHandler->paid($message->getTransactionId(), $context),
                    ConstantsV2::ORDER_CAPTURE_PENDING => $isUnconfirmed ? $this->orderTransactionStateHandler->process($message->getTransactionId(), $context) : null,
                    ConstantsV2::ORDER_CAPTURE_DECLINED, ConstantsV2::ORDER_CAPTURE_FAILED => $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context),
                    default => null,
                };
            } elseif ($order->getIntent() === ConstantsV2::INTENT_AUTHORIZE) {
                match ($order->getPurchaseUnits()->first()?->getPayments()?->getAuthorizations()?->first()?->getStatus()) {
                    ConstantsV2::ORDER_AUTHORIZATION_CAPTURED => $this->orderTransactionStateHandler->paid($message->getTransactionId(), $context),
                    ConstantsV2::ORDER_AUTHORIZATION_PENDING => $isUnconfirmed ? $this->orderTransactionStateHandler->process($message->getTransactionId(), $context) : null,
                    ConstantsV2::ORDER_AUTHORIZATION_CREATED => $isAuthorized ? $this->orderTransactionStateHandler->authorize($message->getTransactionId(), $context) : null,
                    ConstantsV2::ORDER_AUTHORIZATION_VOIDED => $this->orderTransactionStateHandler->cancel($message->getTransactionId(), $context),
                    ConstantsV2::ORDER_AUTHORIZATION_DENIED => $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context),
                    default => null,
                };
            }
        } catch (StateMachineException|PayPalApiException $e) {
            if ($e instanceof PayPalApiException && $e->is(PayPalApiException::ISSUE_INVALID_RESOURCE_ID)) {
                $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context);

                $this->logger->info(
                    'The status sync set the payment of PayPal order {paypalOrderId} to Failed, because PayPal no longer knows the order',
                    [...$logContext, LogKey::CODE => LogCode::STATUS_SYNC_PAYPAL_ORDER_EXPIRED, LogKey::EXCEPTION => $e],
                );

                return;
            }

            if ($e instanceof StateMachineException) {
                $this->logger->error(
                    'The status sync could not change the payment status for PayPal order {paypalOrderId}: {errorCode}, the payment status stays unchanged',
                    [...$logContext, LogKey::CODE => LogCode::STATUS_SYNC_TRANSITION_FAILED, LogKey::EXCEPTION => $e],
                );

                return;
            }

            $this->logger->warning(
                'The status sync could not fetch PayPal order {paypalOrderId}, it tries again with the next run',
                [...$logContext, LogKey::CODE => LogCode::STATUS_SYNC_REQUEST_FAILED, LogKey::EXCEPTION => $e],
            );
        }
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\MessageQueue;

use Monolog\Level;
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
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(TransactionStatusSyncMessage $message): void
    {
        $context = Context::createDefaultContext();

        try {
            if (!$message->getPayPalOrderId()) {
                $this->orderTransactionStateHandler->cancel($message->getTransactionId(), $context);

                return;
            }

            // Check if transaction is still unconfirmed at time of execution
            $criteria = (new Criteria([$message->getTransactionId()]))
                ->addFilter(new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    [
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_UNCONFIRMED),
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_AUTHORIZED),
                        new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                    ]
                ));

            /**
             * @var OrderTransactionEntity|null $transaction
             */
            $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();
            if ($transaction === null) {
                return;
            }

            $order = $this->orderResource->get($message->getPayPalOrderId(), $message->getSalesChannelId());

            if ($order->getIntent() === PaymentIntentV2::CAPTURE) {
                match ($order->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->first()?->getStatus()) {
                    PaymentStatusV2::ORDER_CAPTURE_COMPLETED => $this->orderTransactionStateHandler->paid($message->getTransactionId(), $context),
                    PaymentStatusV2::ORDER_CAPTURE_DECLINED, PaymentStatusV2::ORDER_CAPTURE_FAILED => $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context),
                    default => null,
                };
            } elseif ($order->getIntent() === PaymentIntentV2::AUTHORIZE) {
                match ($order->getPurchaseUnits()->first()?->getPayments()?->getAuthorizations()?->first()?->getStatus()) {
                    PaymentStatusV2::ORDER_AUTHORIZATION_CAPTURED => $this->orderTransactionStateHandler->paid($message->getTransactionId(), $context),
                    PaymentStatusV2::ORDER_AUTHORIZATION_CREATED => $transaction->getStateMachineState()?->getTechnicalName() !== OrderTransactionStates::STATE_AUTHORIZED ? $this->orderTransactionStateHandler->authorize($message->getTransactionId(), $context) : null,
                    PaymentStatusV2::ORDER_AUTHORIZATION_VOIDED => $this->orderTransactionStateHandler->cancel($message->getTransactionId(), $context),
                    PaymentStatusV2::ORDER_AUTHORIZATION_DENIED => $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context),
                    default => null,
                };
            }
        } catch (StateMachineException|PayPalApiException $e) {
            if ($e instanceof PayPalApiException && $e->is(PayPalApiException::ISSUE_INVALID_RESOURCE_ID)) {
                $this->orderTransactionStateHandler->fail($message->getTransactionId(), $context);

                return;
            }

            $this->logger->log(
                $e instanceof StateMachineException ? Level::Error : Level::Warning,
                \sprintf(
                    'Failed to synchronise transaction status for "%s": %s',
                    $message->getTransactionId(),
                    $e->getMessage()
                ),
                ['error' => $e]
            );
        }
    }
}

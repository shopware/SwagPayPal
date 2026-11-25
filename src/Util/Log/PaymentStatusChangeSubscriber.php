<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The checkout, webhooks and the plugin's Administration actions log the cause of a change, `extra.uid` groups both lines.
 * Without a cause line, the status sync or someone outside the plugin changed it, e.g. manually in the Administration.
 *
 * @internal
 */
#[Package('checkout')]
final class PaymentStatusChangeSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StateMachineTransitionEvent::class => 'onTransition',
        ];
    }

    public function onTransition(StateMachineTransitionEvent $event): void
    {
        if ($event->getEntityName() !== OrderTransactionDefinition::ENTITY_NAME) {
            return;
        }

        $criteria = new Criteria([$event->getEntityId()]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('paymentMethod');
        $logContext = [
            Log::ORDER_TRANSACTION_ID => $event->getEntityId(),
            Log::FROM_STATE => $event->getFromPlace()->getTechnicalName(),
            Log::TO_STATE => $event->getToPlace()->getTechnicalName(),
        ];

        // the status has changed already, logging must not fail the transition of whoever triggered it
        try {
            $transaction = $this->orderTransactionRepository->search($criteria, $event->getContext())->getEntities()->first();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Could not check if the changed payment status of order transaction {orderTransactionId} belongs to PayPal',
                Log::context(...$logContext, code: LogCode::PAYMENT_STATUS_LOOKUP_FAILED, exception: $e),
            );

            return;
        }

        $paymentMethod = $transaction?->getPaymentMethod();

        if ($transaction === null || $paymentMethod === null || !$this->paymentMethodDataRegistry->isPayPalPaymentMethod($paymentMethod)) {
            return;
        }

        $this->logger->info(
            'Changed the payment status of order {orderNumber} from {fromState} to {toState}',
            Log::context(...$logContext, code: LogCode::PAYMENT_STATUS_CHANGED, orderTransaction: $transaction),
        );
    }
}

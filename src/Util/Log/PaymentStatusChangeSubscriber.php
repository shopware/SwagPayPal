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
 * Whatever changed the status (checkout, webhook, status sync, Admin) logs the cause itself, `extra.uid` groups both lines.
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
        $transaction = $this->orderTransactionRepository->search($criteria, $event->getContext())->getEntities()->first();
        $paymentMethod = $transaction?->getPaymentMethod();

        if ($transaction === null || $paymentMethod === null || !$this->paymentMethodDataRegistry->isPayPalPaymentMethod($paymentMethod)) {
            return;
        }

        $this->logger->info('Changed the payment status of order {orderNumber} from {fromState} to {toState}', [
            LogKey::ORDER_TRANSACTION => $transaction,
            LogKey::CODE => LogCode::PAYMENT_STATUS_CHANGED,
            LogKey::FROM_STATE => $event->getFromPlace()->getTechnicalName(),
            LogKey::TO_STATE => $event->getToPlace()->getTechnicalName(),
        ]);
    }
}

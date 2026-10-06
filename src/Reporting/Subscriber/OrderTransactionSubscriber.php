<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\Subscriber;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Swag\PayPal\Reporting\DataAbstractionLayer\TransactionReport\TransactionReportCollection;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<TransactionReportCollection> $transactionReportRepository
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly EntityRepository $transactionReportRepository,
        private readonly EntityRepository $orderTransactionRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Unlike `state_enter.order_transaction.state.paid`, this event identifies the transitioned transaction
            'state_machine.order_transaction.state_changed' => 'onTransactionStateChange',
        ];
    }

    public function onTransactionStateChange(StateMachineStateChangeEvent $event): void
    {
        if ($event->getTransitionSide() !== StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            || $event->getNextState()->getTechnicalName() !== OrderTransactionStates::STATE_PAID
            || $event->getContext()->getVersionId() !== Defaults::LIVE_VERSION
        ) {
            return;
        }

        // Internal bookkeeping, must not depend on the ACL privileges of whoever changed the transaction state
        $event->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($event): void {
            $criteria = (new Criteria([$event->getTransition()->getEntityId()]))
                ->addAssociation('paymentMethod')
                ->addAssociation('order.currency');

            $transaction = $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();
            if ($transaction === null) {
                return;
            }

            $handlerId = $transaction->getPaymentMethod()?->getHandlerIdentifier();
            $isSandbox = (bool) $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX);

            if (!\is_string($handlerId)
                || $isSandbox
                || !\in_array($handlerId, $this->methodDataRegistry->getPaymentHandlers(), true)
            ) {
                return;
            }

            $this->transactionReportRepository->upsert([[
                'orderTransactionId' => $transaction->getId(),
                'currencyIso' => $transaction->getOrder()?->getCurrency()?->getIsoCode(),
                'totalPrice' => \round($transaction->getAmount()->getTotalPrice(), 2),
            ]], $context);
        });
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\Subscriber;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly EntityRepository $transactionReportRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_transaction.state.paid' => 'onPaidStateTransition',
        ];
    }

    public function onPaidStateTransition(OrderStateMachineStateChangeEvent $event): void
    {
        // The event does not carry the transitioned transaction, so earlier failed or cancelled ones must be skipped
        $transaction = $event->getOrder()->getTransactions()?->filter(
            static fn (OrderTransactionEntity $transaction): bool => $transaction->getStateMachineState()?->getTechnicalName() === OrderTransactionStates::STATE_PAID
        )->last();
        $handlerId = $transaction?->getPaymentMethod()?->getHandlerIdentifier();
        $isSandbox = (bool) $transaction?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX);

        if ($transaction === null
            || !\is_string($handlerId)
            || $isSandbox
            || $event->getContext()->getVersionId() !== Defaults::LIVE_VERSION
            || !\in_array($handlerId, $this->methodDataRegistry->getPaymentHandlers(), true)
        ) {
            return;
        }

        $data = [
            'orderTransactionId' => $transaction->getId(),
            'currencyIso' => $event->getOrder()->getCurrency()?->getIsoCode(),
            'totalPrice' => \round($transaction->getAmount()->getTotalPrice(), 2),
        ];

        // Internal bookkeeping, must not depend on the ACL privileges of whoever changed the transaction state
        $event->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->transactionReportRepository->upsert([$data], $context);
        });
    }
}

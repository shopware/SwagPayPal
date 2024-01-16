<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\ScheduledTask;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: TransactionStatusSyncTask::class)]
class TransactionStatusSyncTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        // Check all transactions from the last 48h, but offset by an hour
        $hourAgo = (new \DateTime('now -1 hour'))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $twoDaysAgo = (new \DateTime('now -49 hour'))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $criteria = (new Criteria())
            ->addAssociation('order')
            ->addFilter(new EqualsAnyFilter('paymentMethod.handlerIdentifier', $this->methodDataRegistry->getPaymentHandlers()))
            ->addFilter(new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_UNCONFIRMED))
            ->addFilter(new RangeFilter('createdAt', [RangeFilter::LTE => $hourAgo, RangeFilter::GTE => $twoDaysAgo]));

        $transactions = $this->orderTransactionRepository->search($criteria, Context::createDefaultContext());

        /** @var OrderTransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            $orderId = $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);

            if (!\is_string($orderId)) {
                $orderId = null;
            }

            $this->bus->dispatch(new TransactionStatusSyncMessage(
                $transaction->getId(),
                (string) $transaction->getOrder()?->getSalesChannelId(),
                $orderId,
            ));
        }
    }
}

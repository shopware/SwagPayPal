<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * @internal
 */
#[Package('checkout')]
class StockSubscriber implements EventSubscriberInterface
{
    /**
     * Needed for letting the StockUpdater first recalculate availableStock
     */
    private const DELAY = 10000;

    private EntityRepository $orderLineItemRepository;

    private MessageBusInterface $messageBus;

    private EntityRepository $salesChannelRepository;

    public function __construct(
        EntityRepository $orderLineItemRepository,
        MessageBusInterface $messageBus,
        EntityRepository $salesChannelRepository,
    ) {
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->messageBus = $messageBus;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * Returns a list of custom business events to listen where the product maybe changed
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'orderPlaced',
            StateMachineTransitionEvent::class => 'stateChanged',
            OrderEvents::ORDER_LINE_ITEM_WRITTEN_EVENT => 'lineItemWritten',
            OrderEvents::ORDER_LINE_ITEM_DELETED_EVENT => 'lineItemWritten',
        ];
    }

    /**
     * If the product of an order item changed, the stocks of the old product and the new product must be updated.
     */
    public function lineItemWritten(EntityWrittenEvent $event): void
    {
        $ids = [];

        foreach ($event->getWriteResults() as $result) {
            if ($result->hasPayload('referencedId') && $result->getProperty('type') === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $ids[] = $result->getProperty('referencedId');
            }

            if ($result->getOperation() === EntityWriteResult::OPERATION_INSERT) {
                continue;
            }

            $changeSet = $result->getChangeSet();
            if (!$changeSet) {
                continue;
            }

            $type = $changeSet->getBefore('type');

            if ($type !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            if (!$changeSet->hasChanged('referenced_id') && !$changeSet->hasChanged('quantity')) {
                continue;
            }

            $ids[] = $changeSet->getBefore('referenced_id');
            $ids[] = $changeSet->getAfter('referenced_id');
        }

        $this->startSync(\array_unique(\array_filter($ids)), $event->getContext());
    }

    public function stateChanged(StateMachineTransitionEvent $event): void
    {
        $context = $event->getContext();
        if ($context->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        if ($event->getEntityName() !== OrderDefinition::ENTITY_NAME) {
            return;
        }

        $to = $event->getToPlace()->getTechnicalName();
        $from = $event->getFromPlace()->getTechnicalName();

        if ($to !== OrderStates::STATE_COMPLETED && $from !== OrderStates::STATE_COMPLETED
         && $to !== OrderStates::STATE_CANCELLED && $from !== OrderStates::STATE_CANCELLED) {
            return;
        }

        if ($this->posSalesChannelDoesNotExist($context)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $event->getEntityId()));
        $criteria->addFilter(new EqualsFilter('type', LineItem::PRODUCT_LINE_ITEM_TYPE));

        /** @var OrderLineItemCollection $lineItems */
        $lineItems = $this->orderLineItemRepository->search($criteria, $context)->getEntities();

        $ids = [];
        foreach ($lineItems as $lineItem) {
            /* @var OrderLineItemEntity $lineItem */
            $ids[] = $lineItem->getReferencedId();
        }

        $this->startSync($ids, $context);
    }

    public function orderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $ids = [];
        $lineItems = $event->getOrder()->getLineItems();
        if ($lineItems === null) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            $ids[] = $lineItem->getReferencedId();
        }

        $this->startSync($ids, $event->getContext());
    }

    private function posSalesChannelDoesNotExist(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addFilter(new EqualsFilter('active', true));

        return $this->salesChannelRepository->searchIds($criteria, $context)->getTotal() === 0;
    }

    private function startSync(array $productIds, Context $context): void
    {
        if (empty($productIds)) {
            return;
        }

        if ($context->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        if ($this->posSalesChannelDoesNotExist($context)) {
            return;
        }

        $message = new InventoryUpdateMessage();
        $message->setIds($productIds);
        $envelope = new Envelope($message, [
            new DelayStamp(self::DELAY),
        ]);
        $this->messageBus->dispatch($envelope);
    }
}

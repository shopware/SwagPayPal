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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class StockSubscriber implements EventSubscriberInterface
{
    /**
     * Needed for letting the StockUpdater first recalculate availableStock
     */
    private const DELAY = 10000;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderLineItemRepository;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(EntityRepositoryInterface $orderLineItemRepository, MessageBusInterface $messageBus)
    {
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->messageBus = $messageBus;
    }

    /**
     * Returns a list of custom business events to listen where the product maybe changed
     */
    public static function getSubscribedEvents()
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
            // TODO: PPI-65: Change method "getPayload" to "getProperty" if Shopware minVersion > 6.3.2
            if ($result->hasPayload('referencedId') && $result->getPayload()['type'] === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $ids[] = $result->getPayload()['referencedId'];
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

        $this->startSync(\array_filter(\array_unique($ids)), $event->getContext());
    }

    public function stateChanged(StateMachineTransitionEvent $event): void
    {
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $event->getEntityId()));
        $criteria->addFilter(new EqualsFilter('type', LineItem::PRODUCT_LINE_ITEM_TYPE));

        /** @var OrderLineItemCollection $lineItems */
        $lineItems = $this->orderLineItemRepository->search($criteria, $event->getContext())->getEntities();

        $ids = [];
        foreach ($lineItems as $lineItem) {
            /* @var OrderLineItemEntity $lineItem */
            $ids[] = $lineItem->getReferencedId();
        }

        $this->startSync($ids, $event->getContext());
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

    private function startSync(array $productIds, Context $context): void
    {
        if (empty($productIds)) {
            return;
        }

        if ($context->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $message = new InventoryUpdateMessage();
        $message->setIds($productIds);
        $message->setContext($context);
        $envelope = new Envelope($message, [
            new DelayStamp(self::DELAY),
        ]);
        $this->messageBus->dispatch($envelope);
    }
}

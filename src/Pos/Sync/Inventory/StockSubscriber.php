<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory;

use Shopware\Core\Content\Product\Events\ProductStockAlteredEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
class StockSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly EntityRepository $salesChannelRepository,
    ) {
    }

    /**
     * Returns a list of custom business events to listen where the product maybe changed
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductStockAlteredEvent::class => 'updateInventory',
        ];
    }

    public function updateInventory(ProductStockAlteredEvent $event): void
    {
        $this->startSync($event->getIds(), $event->getContext());
    }

    private function posSalesChannelDoesNotExist(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->setLimit(1);

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

        $this->messageBus->dispatch($message);
    }
}

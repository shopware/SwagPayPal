<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\AgentCommerce\Webhook;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly Webhook $webhook,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.written' => 'webhook',
        ];
    }

    public function webhook(EntityWrittenEvent $event): void
    {
        $mapped = [];
        foreach ($event->getWriteResults() as $writeResult) {
            /** @var string $id */
            $id = $writeResult->getPrimaryKey();
            if ($writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE) {
                $mapped[$id] = false;

                continue;
            }

            $active = $writeResult->getProperty('active');
            if ($active === null) {
                continue;
            }

            $mapped[$id] = $active;
        }

        $criteria = new Criteria(array_keys($mapped));
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE));
        $criteria->addAssociations([
            'country',
            'countries',
            'currency',
            'domains',
            'productExports.storefrontSalesChannel.domains',
            'productExports.storefrontSalesChannel.hreflangDefaultDomain',
        ]);

        $result = $this->salesChannelRepository->search($criteria, $event->getContext());
        foreach ($result as $salesChannel) {
            if ($mapped[$salesChannel->getId()]) {
                $this->webhook->register($salesChannel, $event->getContext());
            } else {
                $this->webhook->unregister($salesChannel, $event->getContext());
            }
        }
    }
}

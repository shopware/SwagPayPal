<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits\SalesChannelContextAwareMessageInterface;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class MessageHydrator
{
    protected SalesChannelContextServiceInterface $salesChannelContextService;

    protected EntityRepository $salesChannelRepository;

    /**
     * @internal
     */
    public function __construct(
        SalesChannelContextServiceInterface $salesChannelContextService,
        EntityRepository $salesChannelRepository,
    ) {
        $this->salesChannelContextService = $salesChannelContextService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function hydrateMessage(AbstractSyncMessage $message): void
    {
        if ($message->isHydrated()) {
            return;
        }

        $salesChannel = $this->loadSalesChannel($message->getSalesChannelId(), Context::createDefaultContext());
        $message->setSalesChannel($salesChannel);

        if ($message instanceof SalesChannelContextAwareMessageInterface) {
            $message->setSalesChannelContext($this->loadSalesChannelContext($message));
        }

        if ($message instanceof InventorySyncMessage) {
            $message->getInventoryContext()->setSalesChannel($salesChannel);
        }
    }

    private function loadSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();
        if ($salesChannel === null) {
            throw new SalesChannelNotFoundException();
        }

        return $salesChannel;
    }

    private function loadSalesChannelContext(SalesChannelContextAwareMessageInterface $message): SalesChannelContext
    {
        return $this->salesChannelContextService->get(
            new SalesChannelContextServiceParameters(
                $message->getSalesChannelId(),
                $message->getContextToken()
            )
        );
    }
}

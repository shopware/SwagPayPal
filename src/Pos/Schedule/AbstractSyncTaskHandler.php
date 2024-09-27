<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractSyncTaskHandler extends ScheduledTaskHandler
{
    private EntityRepository $salesChannelRepository;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        EntityRepository $salesChannelRepository,
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        foreach ($this->getSalesChannels($context) as $salesChannel) {
            $this->executeTask($salesChannel, $context);
        }
    }

    abstract protected function executeTask(SalesChannelEntity $salesChannel, Context $context): void;

    private function getSalesChannels(Context $context): SalesChannelCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $criteria->addAssociation('currency');

        /** @var SalesChannelCollection $salesChannels */
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $salesChannels;
    }
}

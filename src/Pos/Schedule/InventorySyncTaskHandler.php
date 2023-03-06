<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Task\InventoryTask;

/**
 * @internal
 */
class InventorySyncTaskHandler extends AbstractSyncTaskHandler
{
    private InventoryTask $inventoryTask;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        EntityRepository $salesChannelRepository,
        InventoryTask $inventoryTask
    ) {
        parent::__construct($scheduledTaskRepository, $salesChannelRepository);
        $this->inventoryTask = $inventoryTask;
    }

    public static function getHandledMessages(): iterable
    {
        return [InventorySyncTask::class];
    }

    protected function executeTask(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->inventoryTask->execute($salesChannel, $context);
    }
}

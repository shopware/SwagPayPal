<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Task\InventoryTask;

class InventorySyncTaskHandler extends AbstractSyncTaskHandler
{
    /**
     * @var InventoryTask
     */
    private $inventoryTask;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $salesChannelRepository,
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

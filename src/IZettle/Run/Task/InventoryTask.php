<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Task;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\InventorySyncer;

class InventoryTask extends AbstractTask
{
    private const TASK_NAME_INVENTORY = 'inventory';

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        InventorySyncer $inventorySyncer
    ) {
        parent::__construct($runService, $logger);
        $this->inventorySyncer = $inventorySyncer;
    }

    public function getRunTaskName(): string
    {
        return self::TASK_NAME_INVENTORY;
    }

    protected function run(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->inventorySyncer->syncInventory($salesChannel, $context);
    }
}

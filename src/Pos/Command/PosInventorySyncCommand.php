<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'swag:paypal:pos:sync:inventory',
    'Sync only inventory to Zettle',
)]
#[Package('checkout')]
class PosInventorySyncCommand extends AbstractPosCommand
{
    private InventoryTask $inventoryTask;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $salesChannelRepository,
        InventoryTask $inventoryTask,
    ) {
        parent::__construct($salesChannelRepository);
        $this->inventoryTask = $inventoryTask;
    }

    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->inventoryTask->execute($salesChannel, $context);
    }
}

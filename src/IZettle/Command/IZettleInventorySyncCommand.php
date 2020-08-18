<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleInventorySyncCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:sync:inventory';

    /**
     * @var InventoryTask
     */
    private $inventoryTask;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        InventoryTask $inventoryTask
    ) {
        parent::__construct($salesChannelRepository);
        $this->inventoryTask = $inventoryTask;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Sync only inventory to iZettle');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->inventoryTask->execute($salesChannel, $context);
    }
}

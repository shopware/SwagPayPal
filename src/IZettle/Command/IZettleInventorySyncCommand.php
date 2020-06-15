<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleInventorySyncCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:sync:inventory';

    /**
     * @var InventoryTask
     */
    private $inventoryTask;

    /**
     * @var RunService
     */
    private $runService;

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        $salesChannels = $this->getSalesChannels($input, $context);

        if ($salesChannels->count() === 0) {
            $output->writeln('No active iZettle sales channel found.');

            return 1;
        }

        foreach ($salesChannels as $salesChannel) {
            $this->inventoryTask->execute($salesChannel, $context);
        }

        return 0;
    }
}

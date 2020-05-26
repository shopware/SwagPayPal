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
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleSyncCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:sync';

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var ProductSyncer
     */
    private $productSyncer;

    /**
     * @var RunService
     */
    private $runService;

    public function __construct(
        ProductSyncer $productSyncer,
        InventorySyncer $inventorySyncer,
        EntityRepositoryInterface $salesChannelRepository,
        RunService $runService
    ) {
        parent::__construct($salesChannelRepository);
        $this->productSyncer = $productSyncer;
        $this->inventorySyncer = $inventorySyncer;
        $this->runService = $runService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Sync to iZettle');
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
            $run = $this->runService->startRun($salesChannel->getId(), $context);
            $this->productSyncer->syncProducts($salesChannel, $context);
            $this->inventorySyncer->syncInventory($salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION), $context);
            $this->runService->finishRun($run, $context);
        }

        return 0;
    }
}

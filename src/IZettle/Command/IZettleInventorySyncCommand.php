<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleInventorySyncCommand extends Command
{
    protected static $defaultName = 'swag:paypal:izettle:inventory:sync';

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    public function __construct(
        InventorySyncer $inventorySyncer,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        parent::__construct();
        $this->inventorySyncer = $inventorySyncer;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Sync inventory to iZettle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE));
        $criteria->addFilter(new EqualsFilter('active', true));

        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        foreach ($salesChannels as $salesChannel) {
            $this->inventorySyncer->syncInventory($salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION), $context);
        }

        return 0;
    }
}

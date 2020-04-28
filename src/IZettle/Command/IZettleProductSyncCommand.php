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
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleProductSyncCommand extends Command
{
    protected static $defaultName = 'swag:paypal:izettle:product:sync';

    /**
     * @var ProductSyncer
     */
    private $productSyncer;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    public function __construct(
        ProductSyncer $productSyncer,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        parent::__construct();
        $this->productSyncer = $productSyncer;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Sync products to iZettle');
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
        $criteria->addAssociation('currency');

        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        foreach ($salesChannels as $salesChannel) {
            $this->productSyncer->syncProducts($salesChannel, $context);
        }

        return 0;
    }
}

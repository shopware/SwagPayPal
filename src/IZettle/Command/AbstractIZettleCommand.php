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
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractIZettleCommand extends Command
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepository;

    public function __construct(EntityRepositoryInterface $salesChannelRepository)
    {
        parent::__construct();
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDefinition([new InputArgument('salesChannelId')]);
    }

    protected function getSalesChannels(InputInterface $input, Context $context): SalesChannelCollection
    {
        $salesChannelId = $input->getArgument('salesChannelId');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('currency');
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION . '.salesChannelDomain');
        if ($salesChannelId !== null) {
            $criteria->setIds([$salesChannelId]);
        }

        /** @var SalesChannelCollection $salesChannels */
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $salesChannels;
    }
}

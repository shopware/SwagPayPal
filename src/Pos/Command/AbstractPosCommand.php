<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('checkout')]
abstract class AbstractPosCommand extends Command
{
    protected EntityRepository $salesChannelRepository;

    /**
     * @internal
     */
    public function __construct(EntityRepository $salesChannelRepository)
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
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        if (!$this->useInactiveSalesChannels()) {
            $criteria->addFilter(new EqualsFilter('active', true));
        }
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $criteria->addAssociation('currency');
        if ($salesChannelId) {
            $criteria->setIds([$salesChannelId]);
        }

        /** @var SalesChannelCollection $salesChannels */
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $salesChannels;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        $salesChannels = $this->getSalesChannels($input, $context);

        if ($salesChannels->count() === 0) {
            $output->writeln('No active Zettle sales channel found.');

            return 1;
        }

        foreach ($salesChannels as $salesChannel) {
            $this->executeForSalesChannel($salesChannel, $output, $context);
            $output->writeln(\sprintf(
                'The task "%s" has been started for sales channel "%s".',
                $this->getDescription() !== '' ? $this->getDescription() : $this->getName() ?? '',
                $salesChannel->getName() ?? $salesChannel->getId()
            ));
        }

        return 0;
    }

    protected function useInactiveSalesChannels(): bool
    {
        return false;
    }

    abstract protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void;
}

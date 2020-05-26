<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Run\LogCleaner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleLogCleanupCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:log:cleanup';

    /**
     * @var LogCleaner
     */
    private $logCleaner;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        LogCleaner $logCleaner
    ) {
        parent::__construct($salesChannelRepository);
        $this->logCleaner = $logCleaner;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Cleanup iZettle sync log');
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
            $this->logCleaner->cleanUpLog($salesChannel->getId(), $context);
        }

        return 0;
    }
}

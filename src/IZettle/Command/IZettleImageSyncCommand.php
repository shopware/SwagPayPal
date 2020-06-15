<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleImageSyncCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:sync:images';

    /**
     * @var ImageTask
     */
    private $imageTask;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        ImageTask $imageTask
    ) {
        parent::__construct($salesChannelRepository);
        $this->imageTask = $imageTask;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Sync only images to iZettle');
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
            $this->imageTask->execute($salesChannel, $context);
        }

        return 0;
    }
}

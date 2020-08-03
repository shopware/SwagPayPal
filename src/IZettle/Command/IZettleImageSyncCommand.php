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
use Swag\PayPal\IZettle\Run\Task\ImageTask;
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
    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->imageTask->execute($salesChannel, $context);
    }
}

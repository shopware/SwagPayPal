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
use Swag\PayPal\IZettle\Run\Administration\SyncResetter;
use Symfony\Component\Console\Output\OutputInterface;

class IZettleSyncResetCommand extends AbstractIZettleCommand
{
    protected static $defaultName = 'swag:paypal:izettle:sync:reset';

    /**
     * @var SyncResetter
     */
    private $resetSyncService;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        SyncResetter $resetSyncService
    ) {
        parent::__construct($salesChannelRepository);
        $this->resetSyncService = $resetSyncService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Reset iZettle sync');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->resetSyncService->resetSync($salesChannel, $context);
    }
}

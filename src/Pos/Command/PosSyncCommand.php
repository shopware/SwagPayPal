<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Symfony\Component\Console\Output\OutputInterface;

class PosSyncCommand extends AbstractPosCommand
{
    protected static $defaultName = 'swag:paypal:pos:sync';

    /**
     * @var CompleteTask
     */
    private $completeTask;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        CompleteTask $completeTask
    ) {
        parent::__construct($salesChannelRepository);
        $this->completeTask = $completeTask;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Sync to Zettle');
    }

    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->completeTask->execute($salesChannel, $context);
    }
}

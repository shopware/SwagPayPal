<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'swag:paypal:pos:sync:images',
    description: 'Sync only images to Zettle',
)]
#[Package('checkout')]
class PosImageSyncCommand extends AbstractPosCommand
{
    private ImageTask $imageTask;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $salesChannelRepository,
        ImageTask $imageTask,
    ) {
        parent::__construct($salesChannelRepository);
        $this->imageTask = $imageTask;
    }

    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->imageTask->execute($salesChannel, $context);
    }
}

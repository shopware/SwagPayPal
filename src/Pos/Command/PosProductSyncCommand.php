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
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'swag:paypal:pos:sync:product',
    description: 'Sync only products to Zettle',
)]
#[Package('checkout')]
class PosProductSyncCommand extends AbstractPosCommand
{
    private ProductTask $productTask;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $salesChannelRepository,
        ProductTask $productTask,
    ) {
        parent::__construct($salesChannelRepository);
        $this->productTask = $productTask;
    }

    protected function executeForSalesChannel(SalesChannelEntity $salesChannel, OutputInterface $output, Context $context): void
    {
        $this->productTask->execute($salesChannel, $context);
    }
}

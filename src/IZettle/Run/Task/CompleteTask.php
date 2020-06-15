<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Task;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;

class CompleteTask extends AbstractTask
{
    private const TASK_NAME_COMPLETE = 'complete';

    /**
     * @var ProductSyncer
     */
    private $productSyncer;

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        ProductSyncer $productSyncer,
        ImageSyncer $imageSyncer,
        InventorySyncer $inventorySyncer
    ) {
        parent::__construct($runService, $logger);
        $this->productSyncer = $productSyncer;
        $this->imageSyncer = $imageSyncer;
        $this->inventorySyncer = $inventorySyncer;
    }

    public function getRunTaskName(): string
    {
        return self::TASK_NAME_COMPLETE;
    }

    protected function run(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->productSyncer->syncProducts($salesChannel, $context);
        $this->imageSyncer->syncImages($this->getIZettleSalesChannel($salesChannel), $context);
        $this->productSyncer->syncProducts($salesChannel, $context);
        $this->inventorySyncer->syncInventory($this->getIZettleSalesChannel($salesChannel), $context);
    }
}

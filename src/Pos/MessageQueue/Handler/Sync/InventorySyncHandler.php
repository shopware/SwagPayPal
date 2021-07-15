<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\InventorySyncer;

class InventorySyncHandler extends AbstractSyncHandler
{
    private EntityRepositoryInterface $productRepository;

    private InventoryContextFactory $inventoryContextFactory;

    private InventorySyncer $inventorySyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        EntityRepositoryInterface $productRepository,
        InventoryContextFactory $inventoryContextFactory,
        InventorySyncer $inventorySyncer
    ) {
        parent::__construct($runService, $logger);
        $this->productRepository = $productRepository;
        $this->inventoryContextFactory = $inventoryContextFactory;
        $this->inventorySyncer = $inventorySyncer;
    }

    /**
     * @param InventorySyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $productIds = $message->getInventoryContext()->getProductIds();
        if ($productIds === null) {
            return;
        }

        $criteria = new Criteria();
        $criteria->setIds($productIds);

        /** @var ProductCollection $products */
        $products = $this->productRepository->search($criteria, $message->getContext())->getEntities();

        $this->inventoryContextFactory->updateLocal($message->getInventoryContext());
        $this->inventorySyncer->sync($products, $message->getInventoryContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            InventorySyncMessage::class,
        ];
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: InventorySyncMessage::class)]
class InventorySyncHandler extends AbstractSyncHandler
{
    private EntityRepository $productRepository;

    private InventoryContextFactory $inventoryContextFactory;

    private InventorySyncer $inventorySyncer;

    /**
     * @internal
     */
    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
        EntityRepository $productRepository,
        InventoryContextFactory $inventoryContextFactory,
        InventorySyncer $inventorySyncer,
    ) {
        parent::__construct($runService, $logger, $messageBus, $messageHydrator);
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
}

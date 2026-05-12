<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: ProductSingleSyncMessage::class)]
class ProductSingleSyncHandler extends AbstractSyncHandler
{
    use PosSalesChannelTrait;

    private ProductSelection $productSelection;

    private SalesChannelRepository $productRepository;

    private ProductSyncer $productSyncer;

    /**
     * @internal
     */
    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
        ProductSelection $productSelection,
        SalesChannelRepository $productRepository,
        ProductSyncer $productSyncer,
    ) {
        parent::__construct($runService, $logger, $messageBus, $messageHydrator);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->productSyncer = $productSyncer;
    }

    /**
     * @param ProductSingleSyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $productStreamId = $this->getPosSalesChannel($message->getSalesChannel())->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $message->getContext());
        $this->productSelection->addAssociations($criteria);
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addFilter(new EqualsFilter('childCount', 0));
        $criteria->setOffset($message->getOffset());
        $criteria->setLimit($message->getLimit());

        /** @var ProductCollection $products */
        $products = $this->productRepository->search($criteria, $message->getSalesChannelContext())->getEntities();

        $this->productSyncer->sync($products, $message->getSalesChannel(), $message->getContext());
    }
}

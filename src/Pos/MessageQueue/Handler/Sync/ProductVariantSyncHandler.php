<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductVariantSyncMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;

class ProductVariantSyncHandler extends AbstractSyncHandler
{
    use PosSalesChannelTrait;

    private ProductSelection $productSelection;

    private SalesChannelRepository $productRepository;

    private ProductSyncer $productSyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        ProductSelection $productSelection,
        SalesChannelRepository $productRepository,
        ProductSyncer $productSyncer
    ) {
        parent::__construct($runService, $logger);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->productSyncer = $productSyncer;
    }

    /**
     * @param ProductVariantSyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $productStreamId = $this->getPosSalesChannel($message->getSalesChannel())->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $message->getContext());
        $this->productSelection->addAssociations($criteria);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsAnyFilter('id', $message->getIds()),
            new EqualsAnyFilter('parentId', $message->getIds()),
        ]));

        /** @var ProductCollection $products */
        $products = $this->productRepository->search($criteria, $message->getSalesChannelContext())->getEntities();

        $this->productSyncer->sync($products, $message->getSalesChannel(), $message->getContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProductVariantSyncMessage::class,
        ];
    }
}

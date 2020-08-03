<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Swag\PayPal\IZettle\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;

class ProductCleanupSyncHandler extends AbstractSyncHandler
{
    /**
     * @var ProductSelection
     */
    private $productSelection;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductSyncer
     */
    private $productSyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        ProductSelection $productSelection,
        SalesChannelRepositoryInterface $productRepository,
        ProductSyncer $productSyncer
    ) {
        parent::__construct($runService, $logger);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->productSyncer = $productSyncer;
    }

    /**
     * @param ProductCleanupSyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $productStreamId = $this->getIZettleSalesChannel($message->getSalesChannel())->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $message->getContext());

        /** @var string[] $productIds */
        $productIds = $this->productRepository->searchIds($criteria, $message->getSalesChannelContext())->getIds();

        $this->productSyncer->cleanUp($productIds, $message->getSalesChannel(), $message->getContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProductCleanupSyncMessage::class,
        ];
    }
}

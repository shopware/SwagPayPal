<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;

class ProductCleanupSyncHandler extends AbstractSyncHandler
{
    use PosSalesChannelTrait;

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

    /**
     * @var EntityRepositoryInterface
     */
    private $posSalesChannelRepository;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        ProductSelection $productSelection,
        SalesChannelRepositoryInterface $productRepository,
        ProductSyncer $productSyncer,
        EntityRepositoryInterface $posSalesChannelRepository
    ) {
        parent::__construct($runService, $logger);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->productSyncer = $productSyncer;
        $this->posSalesChannelRepository = $posSalesChannelRepository;
    }

    /**
     * @param ProductCleanupSyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $productStreamId = $this->getPosSalesChannel($message->getSalesChannel())->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $message->getContext());

        $salesChannelContext = $message->getSalesChannelContext();

        /** @var string[] $productIds */
        $productIds = $salesChannelContext->getContext()->disableCache(
            function () use ($criteria, $salesChannelContext) {
                return $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
            }
        );

        $this->productSyncer->cleanUp($productIds, $message->getSalesChannel(), $message->getContext());

        $posSalesChannel = $this->getPosSalesChannel($message->getSalesChannel());
        if ($posSalesChannel->getReplace() !== PosSalesChannelEntity::REPLACE_ONE_TIME) {
            return;
        }

        $this->posSalesChannelRepository->update([[
            'id' => $posSalesChannel->getId(),
            'replace' => PosSalesChannelEntity::REPLACE_OFF,
        ]], $message->getContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProductCleanupSyncMessage::class,
        ];
    }
}

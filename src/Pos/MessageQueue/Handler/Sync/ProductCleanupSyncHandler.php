<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
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
#[AsMessageHandler(handles: ProductCleanupSyncMessage::class)]
class ProductCleanupSyncHandler extends AbstractSyncHandler
{
    use PosSalesChannelTrait;

    private ProductSelection $productSelection;

    private SalesChannelRepository $productRepository;

    private ProductSyncer $productSyncer;

    private EntityRepository $posSalesChannelRepository;

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
        EntityRepository $posSalesChannelRepository,
    ) {
        parent::__construct($runService, $logger, $messageBus, $messageHydrator);
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
        $productIds = $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();

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
}

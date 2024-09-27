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
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: ImageSyncMessage::class)]
class ImageSyncHandler extends AbstractSyncHandler
{
    private EntityRepository $posMediaRepository;

    private ImageSyncer $imageSyncer;

    /**
     * @internal
     */
    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
        EntityRepository $posMediaRepository,
        ImageSyncer $imageSyncer,
    ) {
        parent::__construct($runService, $logger, $messageBus, $messageHydrator);
        $this->posMediaRepository = $posMediaRepository;
        $this->imageSyncer = $imageSyncer;
    }

    /**
     * @param ImageSyncMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $criteria = $this->imageSyncer->getCriteria($message->getSalesChannel()->getId());
        $criteria->addAssociation('media');
        $criteria->setOffset($message->getOffset());
        $criteria->setLimit($message->getLimit());

        /** @var PosSalesChannelMediaCollection $posMediaCollection */
        $posMediaCollection = $this->posMediaRepository
            ->search($criteria, $message->getContext())
            ->getEntities()
            ->filter(
                static function (PosSalesChannelMediaEntity $entity) {
                    $media = $entity->getMedia();

                    return $entity->getUrl() === null
                        || ($media !== null && $entity->getCreatedAt() < $media->getUpdatedAt());
                }
            );

        $this->imageSyncer->sync($posMediaCollection, $message->getSalesChannel(), $message->getContext());
    }
}

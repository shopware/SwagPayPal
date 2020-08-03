<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaEntity;
use Swag\PayPal\IZettle\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\ImageSyncer;

class ImageSyncHandler extends AbstractSyncHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleMediaRepository;

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        EntityRepositoryInterface $iZettleMediaRepository,
        ImageSyncer $imageSyncer
    ) {
        parent::__construct($runService, $logger);
        $this->iZettleMediaRepository = $iZettleMediaRepository;
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

        /** @var IZettleSalesChannelMediaCollection $iZettleMediaCollection */
        $iZettleMediaCollection = $message->getContext()->disableCache(
            function (Context $context) use ($criteria) {
                return $this->iZettleMediaRepository->search($criteria, $context)->getEntities()->filter(
                    static function (IZettleSalesChannelMediaEntity $entity) {
                        return $entity->getUrl() === null
                            || $entity->getCreatedAt() < $entity->getMedia()->getUpdatedAt();
                    }
                );
            }
        );

        $this->imageSyncer->sync($iZettleMediaCollection, $message->getSalesChannel(), $message->getContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ImageSyncMessage::class,
        ];
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Sync\ImageSyncer;

class ImageSyncHandler extends AbstractSyncHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    private $posMediaRepository;

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        EntityRepositoryInterface $posMediaRepository,
        ImageSyncer $imageSyncer
    ) {
        parent::__construct($runService, $logger);
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
        $posMediaCollection = $message->getContext()->disableCache(
            function (Context $context) use ($criteria) {
                return $this->posMediaRepository->search($criteria, $context)->getEntities()->filter(
                    static function (PosSalesChannelMediaEntity $entity) {
                        return $entity->getUrl() === null
                            || $entity->getCreatedAt() < $entity->getMedia()->getUpdatedAt();
                    }
                );
            }
        );

        $this->imageSyncer->sync($posMediaCollection, $message->getSalesChannel(), $message->getContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ImageSyncMessage::class,
        ];
    }
}

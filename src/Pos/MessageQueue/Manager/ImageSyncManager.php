<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Exception\MediaDomainNotSetException;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;

#[Package('checkout')]
class ImageSyncManager extends AbstractSyncManager
{
    use PosSalesChannelTrait;

    public const CHUNK_SIZE = 250;

    private EntityRepository $posMediaRepository;

    private ImageSyncer $imageSyncer;

    /**
     * @internal
     */
    public function __construct(
        MessageDispatcher $messageBus,
        EntityRepository $posMediaRepository,
        ImageSyncer $imageSyncer,
    ) {
        parent::__construct($messageBus);
        $this->posMediaRepository = $posMediaRepository;
        $this->imageSyncer = $imageSyncer;
    }

    /**
     * @return AbstractSyncMessage[]
     */
    public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): array
    {
        $domain = $this->getPosSalesChannel($salesChannel)->getMediaDomain();

        if ($domain === null || $domain === '') {
            throw new MediaDomainNotSetException($salesChannel->getId());
        }

        $criteria = $this->imageSyncer->getCriteria($salesChannel->getId());
        $criteria->addAggregation(new CountAggregation('count', 'mediaId'));

        /** @var CountResult|null $aggregate */
        $aggregate = $this->posMediaRepository->aggregate($criteria, $context)->get('count');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }

        $offset = 0;
        $messages = [];

        while ($offset < $aggregate->getCount()) {
            $message = new ImageSyncMessage();
            $message->setRunId($runId);
            $message->setLimit(self::CHUNK_SIZE);
            $message->setOffset($offset);
            $message->setSalesChannel($salesChannel);
            $messages[] = $message;

            $offset += self::CHUNK_SIZE;
        }

        return $messages;
    }
}

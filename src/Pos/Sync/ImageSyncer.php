<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Image\BulkImageUpload;
use Swag\PayPal\Pos\Api\Image\BulkImageUploadResponse\Uploaded;
use Swag\PayPal\Pos\Api\Service\MediaConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\Exception\InvalidMediaTypeException;
use Swag\PayPal\Pos\Exception\MediaDomainNotSetException;
use Swag\PayPal\Pos\Resource\ImageResource;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;

class ImageSyncer
{
    use PosSalesChannelTrait;

    /**
     * @var EntityRepositoryInterface
     */
    private $posMediaRepository;

    /**
     * @var MediaConverter
     */
    private $mediaConverter;

    /**
     * @var ImageResource
     */
    private $imageResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $posMediaRepository,
        MediaConverter $mediaConverter,
        ImageResource $imageResource,
        LoggerInterface $logger
    ) {
        $this->posMediaRepository = $posMediaRepository;
        $this->mediaConverter = $mediaConverter;
        $this->imageResource = $imageResource;
        $this->logger = $logger;
    }

    /**
     * @param PosSalesChannelMediaCollection $entityCollection
     */
    public function sync(
        EntityCollection $entityCollection,
        SalesChannelEntity $salesChannel,
        Context $context
    ): void {
        $posSalesChannel = $this->getPosSalesChannel($salesChannel);

        $domain = \rtrim($posSalesChannel->getMediaDomain() ?? '', '/');

        if ($domain === '') {
            throw new MediaDomainNotSetException($salesChannel->getId());
        }

        $bulkUpload = new BulkImageUpload();

        foreach ($entityCollection as $entity) {
            try {
                /* @var PosSalesChannelMediaEntity $entity */
                $upload = $this->mediaConverter->convert($domain, $entity->getMedia(), $entity->getLookupKey());

                $bulkUpload->addImageUpload($upload);
            } catch (InvalidMediaTypeException $invalidMediaTypeException) {
                $this->logger->warning(
                    'Media Type {mimeType} is not supported by Zettle. Skipping image {fileName}.',
                    [
                        'mimeType' => $entity->getMedia()->getMimeType(),
                        'fileName' => $entity->getMedia()->getFileName() . '.' . $entity->getMedia()->getFileExtension(),
                    ]
                );
            }
        }

        $response = $this->imageResource->bulkUploadPictures($posSalesChannel, $bulkUpload);
        if ($response === null) {
            return;
        }

        $updates = [];
        foreach ($response->getUploaded() as $uploaded) {
            $update = $this->prepareMediaUpdate($entityCollection, $uploaded, $posSalesChannel->getSalesChannelId());
            if ($update !== null) {
                $updates[] = $update;
            }
        }

        if (\count($updates) > 0) {
            $this->posMediaRepository->upsert($updates, $context);
            $this->logger->info('Successfully uploaded {count} images.', [
                'count' => \count($updates),
            ]);
        }

        foreach ($response->getInvalid() as $invalid) {
            $this->logger->warning('Upload was not accepted by Zettle (is the URL publicly available?): {invalid}', [
                'invalid' => $invalid,
            ]);
        }
    }

    public function getCriteria(string $salesChannelId): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salesChannelId', $salesChannelId),
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('url', null),
                new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('media.updatedAt', null)]),
            ])
        );

        return $criteria;
    }

    public function cleanUp(string $salesChannelId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salesChannelId', $salesChannelId),
            new EqualsFilter('url', null),
            new EqualsFilter('lookupKey', null)
        );

        $ids = $this->posMediaRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'mediaId' => $id['media_id']];
            }, $ids);
            $this->posMediaRepository->delete($ids, $context);
        }
    }

    private function prepareMediaUpdate(
        PosSalesChannelMediaCollection $posMediaCollection,
        Uploaded $uploaded,
        string $salesChannelId
    ): ?array {
        $urlPath = \parse_url($uploaded->getSource(), \PHP_URL_PATH);

        if (\is_string($urlPath)) {
            $posMedia = $posMediaCollection->filter(
                static function (PosSalesChannelMediaEntity $entity) use ($urlPath) {
                    return \mb_strpos($urlPath, $entity->getMedia()->getUrl()) !== false
                        || \mb_strpos($entity->getMedia()->getUrl(), $urlPath) !== false;
                }
            )->first();
        } else {
            $posMedia = null;
        }

        if ($posMedia === null) {
            $this->logger->warning('Could not match uploaded image to local media: {posUrl}', [
                'posUrl' => \current($uploaded->getImageUrls()),
            ]);

            return null;
        }

        return [
            'salesChannelId' => $salesChannelId,
            'mediaId' => $posMedia->getMedia()->getId(),
            'lookupKey' => $uploaded->getImageLookupKey(),
            'url' => \current($uploaded->getImageUrls()),
        ];
    }
}

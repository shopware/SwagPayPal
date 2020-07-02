<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Swag\PayPal\IZettle\Api\Image\BulkImageUpload;
use Swag\PayPal\IZettle\Api\Image\BulkImageUploadResponse\Uploaded;
use Swag\PayPal\IZettle\Api\Service\MediaConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaEntity;
use Swag\PayPal\IZettle\Exception\InvalidMediaTypeException;
use Swag\PayPal\IZettle\Exception\NoDomainAssignedException;
use Swag\PayPal\IZettle\Resource\ImageResource;

class ImageSyncer
{
    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleMediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelDomainRepository;

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
        EntityRepositoryInterface $iZettleMediaRepository,
        MediaConverter $mediaConverter,
        ImageResource $imageResource,
        LoggerInterface $logger
    ) {
        $this->iZettleMediaRepository = $iZettleMediaRepository;
        $this->mediaConverter = $mediaConverter;
        $this->imageResource = $imageResource;
        $this->logger = $logger;
    }

    public function syncImages(
        IZettleSalesChannelEntity $iZettleSalesChannel,
        Context $context
    ): void {
        $iZettleMediaCollection = $this->getIZettleMediaCollection($iZettleSalesChannel, $context);

        $domain = $iZettleSalesChannel->getSalesChannelDomain();
        if ($domain === null) {
            throw new NoDomainAssignedException($iZettleSalesChannel->getSalesChannelId());
        }

        $bulkUpload = new BulkImageUpload();

        foreach ($iZettleMediaCollection as $entity) {
            try {
                /* @var IZettleSalesChannelMediaEntity $entity */
                $upload = $this->mediaConverter->convert($domain, $entity->getMedia(), $entity->getLookupKey());

                $bulkUpload->addImageUpload($upload);
            } catch (InvalidMediaTypeException $invalidMediaTypeException) {
                $this->logger->warning(
                    'Media Type {mimeType} is not supported by iZettle. Skipping image {fileName}.',
                    [
                        'mimeType' => $entity->getMedia()->getMimeType(),
                        'fileName' => $entity->getMedia()->getFileName() . '.' . $entity->getMedia()->getFileExtension(),
                    ]
                );
            }
        }

        $response = $this->imageResource->bulkUploadPictures($iZettleSalesChannel, $bulkUpload);
        if ($response === null) {
            return;
        }

        $updates = [];
        foreach ($response->getUploaded() as $uploaded) {
            $update = $this->prepareMediaUpdate($iZettleMediaCollection, $uploaded, $iZettleSalesChannel->getSalesChannelId());
            if ($update !== null) {
                $updates[] = $update;
            }
        }

        if (\count($updates) > 0) {
            $this->iZettleMediaRepository->upsert($updates, $context);
            $this->logger->info('Successfully uploaded {count} images.', [
                'count' => \count($updates),
            ]);
        }

        foreach ($response->getInvalid() as $invalid) {
            $this->logger->warning('Upload was not accepted by iZettle (is the URL publicly available?): {invalid}', [
                'invalid' => $invalid,
            ]);
        }
    }

    private function getIZettleMediaCollection(IZettleSalesChannelEntity $iZettleSalesChannel, Context $context): IZettleSalesChannelMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('media');
        $criteria->addFilter(
            new EqualsFilter('salesChannelId', $iZettleSalesChannel->getSalesChannelId()),
            new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('url', null),
                new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('media.updatedAt', null)]),
            ])
        );

        /** @var IZettleSalesChannelMediaCollection $iZettleMediaCollection */
        $iZettleMediaCollection = $this->iZettleMediaRepository->search($criteria, $context)->getEntities()->filter(
            static function (IZettleSalesChannelMediaEntity $entity) {
                return $entity->getUrl() === null
                    || $entity->getCreatedAt() < $entity->getMedia()->getUpdatedAt();
            }
        );

        return $iZettleMediaCollection;
    }

    private function prepareMediaUpdate(
        IZettleSalesChannelMediaCollection $iZettleMediaCollection,
        Uploaded $uploaded,
        string $salesChannelId
    ): ?array {
        $urlPath = \parse_url($uploaded->getSource(), PHP_URL_PATH);

        if (\is_string($urlPath)) {
            $iZettleMedia = $iZettleMediaCollection->filter(
                static function (IZettleSalesChannelMediaEntity $entity) use ($urlPath) {
                    return \mb_strpos($urlPath, $entity->getMedia()->getUrl()) !== false
                        || \mb_strpos($entity->getMedia()->getUrl(), $urlPath) !== false;
                }
            )->first();
        } else {
            $iZettleMedia = null;
        }

        if ($iZettleMedia === null) {
            $this->logger->warning('Could not match uploaded image to local media: {iZettleUrl}', [
                'iZettleUrl' => \current($uploaded->getImageUrls()),
            ]);

            return null;
        }

        return [
            'salesChannelId' => $salesChannelId,
            'mediaId' => $iZettleMedia->getMedia()->getId(),
            'lookupKey' => $uploaded->getImageLookupKey(),
            'url' => \current($uploaded->getImageUrls()),
        ];
    }
}

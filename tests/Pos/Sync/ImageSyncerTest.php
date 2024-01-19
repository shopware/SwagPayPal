<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Image\BulkImageUpload;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Api\Service\MediaConverter;
use Swag\PayPal\Pos\Client\PosClient;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\Exception\MediaDomainNotSetException;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ImageSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Resource\ImageResource;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosMediaRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class ImageSyncerTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private const DOMAIN_URL = 'https://some.shopware.domain';
    private const INVALID_MIME_TYPE = 'video/mp4';
    private const LOCAL_FILE_NAME = 'file';
    private const LOCAL_FILE_EXTENSION = 'jpg';
    private const MEDIA_ID_1 = 'mediaId1';
    private const MEDIA_ID_2 = 'mediaId2';
    private const MEDIA_ID_3 = 'mediaId3';
    private const MEDIA_ID_4 = 'mediaId4';
    private const MEDIA_URL_VALID = 'validUrl.jpg';
    private const MEDIA_URL_INVALID = 'test/invalid Url.jpg';
    private const MEDIA_URL_INVALID_ENCODED = 'test/invalid%20Url.jpg';
    private const MEDIA_URL_EXISTING = 'existingUrl.jpg';
    private const POS_IMAGE_URL = 'https://image.izettle.com/product/BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ.jpeg';
    private const POS_IMAGE_URL_EXISTING = 'https://image.izettle.com/product/CJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ.jpeg';
    private const POS_IMAGE_URL_INVALID = 'https://image.izettle.com/product/AJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ.jpeg';
    private const POS_IMAGE_LOOKUP_KEY = 'BJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ';
    private const POS_IMAGE_LOOKUP_KEY_EXISTING = 'CJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ';
    private const POS_IMAGE_LOOKUP_KEY_INVALID = 'AJfd5OBOEemBrw-6zpwgaA-F1EGGBqgEeq0Zcced6LHlQ';
    private const INVALID_SOURCE_URL = 'https://media3.giphy.com/media/3oeSAF90T9N04MyefS/giphy.gif';

    #[DataProvider('dataProviderImageSync')]
    public function testImageSync(string $mediaDomain): void
    {
        $context = Context::createDefaultContext();
        $imageResource = $this->createImageResource();
        $logger = $this->createLogger();

        $mediaRepository = new PosMediaRepoMock();

        $mediaA = $this->createMedia(self::MEDIA_ID_1, self::MEDIA_URL_VALID);
        $mediaRepository->addMockEntity($mediaA);
        $mediaB = $this->createMedia(self::MEDIA_ID_2, self::MEDIA_URL_INVALID);
        $mediaRepository->addMockEntity($mediaB);
        $mediaC = $this->createMedia(self::MEDIA_ID_3, self::MEDIA_URL_VALID, null, false);
        $mediaRepository->addMockEntity($mediaC);
        $mediaD = $this->createMedia(self::MEDIA_ID_4, self::DOMAIN_URL . '/' . self::MEDIA_URL_EXISTING, self::POS_IMAGE_LOOKUP_KEY_EXISTING);
        $mediaRepository->addMockEntity($mediaD);

        $imageSyncer = new ImageSyncer(
            $mediaRepository,
            new MediaConverter(),
            $imageResource,
            $logger
        );

        $messageBus = new MessageBusMock();
        $messageDispatcher = new MessageDispatcher($messageBus, $this->createMock(Connection::class));
        $messageHydrator = new MessageHydrator($this->createMock(SalesChannelContextService::class), $this->createMock(EntityRepository::class));
        $runService = new RunServiceMock(
            new RunRepoMock(),
            new RunLogRepoMock(),
            $this->createMock(Connection::class),
            new Logger('test')
        );

        $imageSyncHandler = new ImageSyncHandler(
            $runService,
            $logger,
            $messageDispatcher,
            $messageHydrator,
            $mediaRepository,
            $imageSyncer
        );

        $imageSyncManager = new ImageSyncManager($messageDispatcher, $mediaRepository, $imageSyncer);

        $salesChannel = $this->getSalesChannel($context);
        $posSalesChannel = $salesChannel->getExtensionOfType(SwagPayPal::SALES_CHANNEL_POS_EXTENSION, PosSalesChannelEntity::class);
        static::assertNotNull($posSalesChannel);
        $posSalesChannel->setMediaDomain($mediaDomain);

        $runId = $runService->startRun(TestDefaults::SALES_CHANNEL, 'image', [SyncManagerHandler::SYNC_IMAGE], $context);

        $messages = $imageSyncManager->createMessages($salesChannel, $context, $runId);
        $messageDispatcher->bulkDispatch($messages, $runId);
        $messageBus->execute([$imageSyncHandler]);

        static::assertSame(self::POS_IMAGE_URL, $mediaA->getUrl());
        static::assertSame(self::POS_IMAGE_LOOKUP_KEY, $mediaA->getLookupKey());
        static::assertNull($mediaB->getUrl());
        static::assertNull($mediaB->getLookupKey());
        static::assertNull($mediaC->getUrl());
        static::assertNull($mediaC->getLookupKey());
        static::assertSame(self::POS_IMAGE_URL_EXISTING, $mediaD->getUrl());
        static::assertSame(self::POS_IMAGE_LOOKUP_KEY_EXISTING, $mediaD->getLookupKey());
    }

    public static function dataProviderImageSync(): array
    {
        return [
            [self::DOMAIN_URL],
            [self::DOMAIN_URL . '/'],
        ];
    }

    public function testNoMediaUrl(): void
    {
        $context = Context::createDefaultContext();

        $imageSyncer = new ImageSyncer(
            new PosMediaRepoMock(),
            new MediaConverter(),
            new ImageResource(new PosClientFactoryMock()),
            new NullLogger()
        );

        $messageDispatcher = new MessageDispatcher(new MessageBusMock(), $this->createMock(Connection::class));
        $runService = new RunServiceMock(
            new RunRepoMock(),
            new RunLogRepoMock(),
            $this->createMock(Connection::class),
            new Logger('test')
        );

        $imageSyncManager = new ImageSyncManager($messageDispatcher, new PosMediaRepoMock(), $imageSyncer);

        $salesChannel = $this->getSalesChannel($context);
        $posSalesChannel = $salesChannel->getExtensionOfType(SwagPayPal::SALES_CHANNEL_POS_EXTENSION, PosSalesChannelEntity::class);
        static::assertNotNull($posSalesChannel);
        $posSalesChannel->setMediaDomain(null);

        $runId = $runService->startRun(TestDefaults::SALES_CHANNEL, 'image', [SyncManagerHandler::SYNC_IMAGE], $context);

        $this->expectException(MediaDomainNotSetException::class);
        $imageSyncManager->createMessages($salesChannel, $context, $runId);
    }

    public function testCleanUp(): void
    {
        $mediaRepo = $this->createMock(EntityRepository::class);
        $imageSyncer = new ImageSyncer(
            $mediaRepo,
            $this->createMock(MediaConverter::class),
            $this->createMock(ImageResource::class),
            new NullLogger()
        );
        $context = Context::createDefaultContext();
        $salesChannelId = Uuid::randomHex();
        $mediaId = Uuid::randomHex();

        $mediaRepo->expects(static::once())->method('searchIds')->willReturn(
            new IdSearchResult(
                1,
                [
                    [
                        'primaryKey' => ['salesChannelId' => $salesChannelId, 'mediaId' => $mediaId],
                        'data' => [],
                    ],
                ],
                new Criteria(),
                $context
            ),
        );
        $mediaRepo->expects(static::once())->method('delete')->with(
            [
                ['salesChannelId' => $salesChannelId, 'mediaId' => $mediaId],
            ],
            $context
        );

        $imageSyncer->cleanUp($salesChannelId, $context);
    }

    private function createMedia(string $id, string $url, ?string $lookupKey = null, bool $validMime = true): PosSalesChannelMediaEntity
    {
        $posMedia = new PosSalesChannelMediaEntity();
        $media = new MediaEntity();
        $media->setId($id);
        $media->setUrl($url);
        $media->setPath(\str_replace(self::DOMAIN_URL . '/', '', $url));
        $media->setFileName(self::LOCAL_FILE_NAME);
        $media->setFileExtension(self::LOCAL_FILE_EXTENSION);
        $media->setMimeType($validMime ? 'image/jpeg' : self::INVALID_MIME_TYPE);
        $posMedia->setMedia($media);
        $posMedia->setMediaId($media->getId());
        $posMedia->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $posMedia->setUniqueIdentifier(TestDefaults::SALES_CHANNEL . '-' . $id);
        $posMedia->setLookupKey($lookupKey);

        return $posMedia;
    }

    private function createLogger(): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('info')->with(
            'Successfully uploaded {count} images.',
            ['count' => 2]
        );
        $matcher = static::exactly(3);
        $logger->expects($matcher)->method('warning')->willReturnCallback(function (string $message, array $context) use ($matcher): void {
            switch ($matcher->numberOfInvocations()) {
                case 1:
                    static::assertSame('Media Type {mimeType} is not supported by Zettle. Skipping image {fileName}.', $message);
                    static::assertSame(self::INVALID_MIME_TYPE, $context['mimeType']);
                    static::assertSame(self::LOCAL_FILE_NAME . '.' . self::LOCAL_FILE_EXTENSION, $context['fileName']);

                    break;
                case 2:
                    static::assertSame('Could not match uploaded image to local media: {posUrl}', $message);
                    static::assertSame(self::POS_IMAGE_URL_INVALID, $context['posUrl']);

                    break;
                case 3:
                    static::assertSame('Upload was not accepted by Zettle (is the URL publicly available?): {invalid}', $message);
                    static::assertSame(self::DOMAIN_URL . self::MEDIA_URL_INVALID_ENCODED, $context['invalid']);

                    break;
                default:
                    static::fail('Unexpected call to logger');
            }
        });

        return $logger;
    }

    private function createImageResource(): ImageResource
    {
        $client = $this->createMock(PosClient::class);
        $client->expects(static::once())->method('sendPostRequest')->with(
            PosRequestUri::IMAGE_RESOURCE_BULK,
            (new BulkImageUpload())->assign(['imageUploads' => [
                [
                    'imageFormat' => 'JPEG',
                    'imageUrl' => self::DOMAIN_URL . '/' . self::MEDIA_URL_VALID,
                ],
                [
                    'imageFormat' => 'JPEG',
                    'imageUrl' => self::DOMAIN_URL . '/' . self::MEDIA_URL_INVALID_ENCODED,
                ],
                [
                    'imageFormat' => 'JPEG',
                    'imageUrl' => self::DOMAIN_URL . '/' . self::MEDIA_URL_EXISTING,
                    'imageLookupKey' => self::POS_IMAGE_LOOKUP_KEY_EXISTING,
                ],
            ]])
        )->willReturn([
            'invalid' => [self::DOMAIN_URL . self::MEDIA_URL_INVALID_ENCODED],
            'uploaded' => [
                [
                    'imageLookupKey' => self::POS_IMAGE_LOOKUP_KEY,
                    'imageUrls' => [self::POS_IMAGE_URL],
                    'source' => self::DOMAIN_URL . '/' . self::MEDIA_URL_VALID,
                ],
                [
                    'imageLookupKey' => self::POS_IMAGE_LOOKUP_KEY_INVALID,
                    'imageUrls' => [self::POS_IMAGE_URL_INVALID],
                    'source' => self::INVALID_SOURCE_URL,
                ],
                [
                    'imageLookupKey' => self::POS_IMAGE_LOOKUP_KEY_EXISTING,
                    'imageUrls' => [self::POS_IMAGE_URL_EXISTING],
                    'source' => self::DOMAIN_URL . '/' . self::MEDIA_URL_EXISTING,
                ],
            ],
        ]);

        $clientFactory = $this->createMock(PosClientFactory::class);
        $clientFactory->method('getPosClient')->willReturn($client);

        return new ImageResource($clientFactory);
    }
}

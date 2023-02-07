<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGenerator;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Api\Product\Presentation;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\Pos\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\Pos\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PriceConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\Converter\VariantConverter;
use Swag\PayPal\Pos\Api\Service\MediaConverter;
use Swag\PayPal\Pos\Api\Service\MetadataGenerator;
use Swag\PayPal\Pos\Api\Service\ProductConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductCleanupSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductSingleSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\ProductVariantSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Resource\ImageResource;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\Pos\Sync\Context\ProductContextFactory;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\Pos\Sync\Product\DeletedUpdater;
use Swag\PayPal\Pos\Sync\Product\NewUpdater;
use Swag\PayPal\Pos\Sync\Product\OutdatedUpdater;
use Swag\PayPal\Pos\Sync\Product\UnsyncedChecker;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Pos\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\CreateProductFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\DeleteProductsFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\UpdateProductFixture;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosMediaRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;

class CompleteProductTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private const MEDIA_UPLOADED_URL = 'https://via.placeholder.com/500x500';
    private const MEDIA_A_ID = 'd7f9cc539a9c463ab95f15884872aad7';
    private const MEDIA_B_ID = '42e1a918149e4f599e16ca672e0f39e8';

    public function testProductSync(): void
    {
        $productResource = new ProductResource(new PosClientFactoryMock());
        $posProductRepository = new PosProductRepoMock();
        $posMediaRepository = new PosMediaRepoMock();
        $productRepository = new ProductRepoMock();
        $salesChannelProductRepository = new SalesChannelProductRepoMock();

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get('Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory');

        $productConverter = new ProductConverter(
            new UuidConverter(),
            new CategoryConverter(new UuidConverter()),
            new VariantConverter(
                new UuidConverter(),
                new PriceConverter(),
                new PresentationConverter(),
                new NullLogger()
            ),
            new OptionGroupConverter(),
            new PresentationConverter(),
            new MetadataGenerator()
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

        $productSyncer = new ProductSyncer(
            $productConverter,
            new ProductContextFactory(
                $posProductRepository,
                $posMediaRepository
            ),
            new NewUpdater(
                $productResource,
                new NullLogger()
            ),
            new OutdatedUpdater(
                $productResource,
                new NullLogger()
            ),
            new DeletedUpdater(
                $productResource,
                new NullLogger(),
                new UuidConverter()
            ),
            new UnsyncedChecker(
                $productResource,
                new NullLogger(),
                new UuidConverter()
            )
        );

        $productSelection = new ProductSelection(
            $salesChannelProductRepository,
            $this->createMock(ProductStreamBuilder::class),
            $salesChannelContextFactory
        );

        $productSyncManager = new ProductSyncManager(
            $messageDispatcher,
            $productSelection,
            $salesChannelProductRepository,
            new ImageSyncer(
                $posMediaRepository,
                new MediaConverter($this->createMock(UrlGenerator::class)),
                new ImageResource(new PosClientFactoryMock()),
                new NullLogger()
            )
        );

        $productVariantSyncHandler = new ProductVariantSyncHandler(
            $runService,
            new NullLogger(),
            $messageDispatcher,
            $messageHydrator,
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer
        );

        $productSingleSyncHandler = new ProductSingleSyncHandler(
            $runService,
            new NullLogger(),
            $messageDispatcher,
            $messageHydrator,
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer
        );

        $productCleanupSyncHandler = new ProductCleanupSyncHandler(
            $runService,
            new NullLogger(),
            $messageDispatcher,
            $messageHydrator,
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer,
            new SalesChannelRepoMock()
        );

        $context = Context::createDefaultContext();
        $salesChannel = $this->getSalesChannel($context);
        $tax = $this->getTax();
        $category = $this->getCategory();
        $currency = $salesChannel->getCurrency();
        static::assertNotNull($currency);
        $mediaA = $this->getMedia(self::MEDIA_A_ID, 'first.jpg');
        $mediaB = $this->getMedia(self::MEDIA_B_ID, 'second.jpg');
        $mediaC = $this->getMedia(Uuid::randomHex(), 'non_existing.jpg');

        /*
          * A - unchanged
          * B - new (with variants A, B)
          * C - new (simple), with non-uploaded media
          * D - updated (simple)
          * E - updated (with variants C, D), with uploaded media
          * F - removed
          * G - manually added to Zettle
          */
        $productA = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productA', ConstantsForTesting::PRODUCT_A_ID);
        $productRepository->addMockEntity($productA);
        $productB = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productB', ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($productB);
        $variantA = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productB_variantA', ConstantsForTesting::VARIANT_A_ID, ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($variantA);
        $variantB = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productB_variantB', ConstantsForTesting::VARIANT_B_ID, ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($variantB);
        $productB->setChildCount(2);
        $productC = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productC', ConstantsForTesting::PRODUCT_C_ID, null, $mediaB);
        $productRepository->addMockEntity($productC);
        $productD = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productD', ConstantsForTesting::PRODUCT_D_ID);
        $productRepository->addMockEntity($productD);
        $productE = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productE', ConstantsForTesting::PRODUCT_E_ID);
        $productRepository->addMockEntity($productE);
        $variantC = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productE_variantC', ConstantsForTesting::VARIANT_C_ID, ConstantsForTesting::PRODUCT_E_ID, $mediaA);
        $productRepository->addMockEntity($variantC);
        $variantD = $salesChannelProductRepository->createMockEntity($tax, $category, $currency, 'productE_variantD', ConstantsForTesting::VARIANT_D_ID, ConstantsForTesting::PRODUCT_E_ID, $mediaA);
        $productRepository->addMockEntity($variantD);
        $productE->setChildCount(2);
        static::assertCount(9, $productRepository->getCollection());

        // create current checksum for unchanged product A
        $convertedGroupingA = $productConverter->convertShopwareProducts(
            new ProductCollection([$productA]),
            $currency,
            new ProductContext($salesChannel, new PosSalesChannelProductCollection([]), new PosSalesChannelMediaCollection([]), $context)
        )->first();
        static::assertNotNull($convertedGroupingA);

        $productStateA = $posProductRepository->createMockEntity($productA, $convertedGroupingA->getProduct(), TestDefaults::SALES_CHANNEL);
        $productStateD = $posProductRepository->createMockEntity($productD, new Product(), TestDefaults::SALES_CHANNEL);
        $productStateE = $posProductRepository->createMockEntity($productE, new Product(), TestDefaults::SALES_CHANNEL);
        $deletedProductF = new ProductEntity();
        $deletedProductF->setId(ConstantsForTesting::PRODUCT_F_ID);
        $deletedProductF->setVersionId(Uuid::randomHex());
        $productStateF = $posProductRepository->createMockEntity($deletedProductF, new Product(), TestDefaults::SALES_CHANNEL);
        static::assertCount(4, $posProductRepository->getCollection());

        $existingMedia = $posMediaRepository->createMockEntity($mediaA, TestDefaults::SALES_CHANNEL, 'lookupKey', self::MEDIA_UPLOADED_URL);
        $removableMedia = $posMediaRepository->createMockEntity($mediaC, TestDefaults::SALES_CHANNEL);

        $runId = $runService->startRun(TestDefaults::SALES_CHANNEL, 'product', [], $context);
        $messages = $productSyncManager->createMessages($salesChannel, $context, $runId);

        $messageDispatcher->bulkDispatch($messages, $runId);
        $messageBus->execute([
            $productSingleSyncHandler,
            $productVariantSyncHandler,
            $productCleanupSyncHandler,
        ]);

        static::assertCount(5, $posProductRepository->getCollection());
        static::assertNotContains($productStateF, $posProductRepository->getCollection());
        static::assertSame($convertedGroupingA->getProduct()->generateChecksum(), $productStateA->getChecksum());
        static::assertNotEquals((new Product())->generateChecksum(), $productStateD->getChecksum());
        static::assertNotEquals((new Product())->generateChecksum(), $productStateE->getChecksum());

        static::assertEqualsCanonicalizing(
            [ConstantsForTesting::PRODUCT_F_ID_CONVERTED, ConstantsForTesting::PRODUCT_G_ID_CONVERTED],
            DeleteProductsFixture::$deletedUuids
        );

        static::assertEquals($this->createConvertedProduct($productB, $variantA, $variantB), CreateProductFixture::$lastCreatedProducts[1]);
        $productC->assign(['cover' => null]);
        static::assertEquals($this->createConvertedProduct($productC, $productC), CreateProductFixture::$lastCreatedProducts[0]);
        static::assertEquals($this->createConvertedProduct($productD, $productD), UpdateProductFixture::$lastUpdatedProducts[0]);
        static::assertEquals($this->createConvertedProduct($productE, $variantC, $variantD), UpdateProductFixture::$lastUpdatedProducts[1]);
        static::assertCount(2, CreateProductFixture::$lastCreatedProducts);
        static::assertCount(2, UpdateProductFixture::$lastUpdatedProducts);

        static::assertCount(2, $posMediaRepository->getCollection());
        static::assertContains($existingMedia, $posMediaRepository->getCollection());
        static::assertNotContains($removableMedia, $posMediaRepository->getCollection());
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        /** @var EntityRepository $taxRepository */
        $taxRepository = $this->getContainer()->get('tax.repository');
        $tax = $taxRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($tax);

        return $tax;
    }

    private function getMedia(string $id, string $url): MediaEntity
    {
        $media = new MediaEntity();
        $media->setId($id);
        $media->setUrl($url);
        $media->setMimeType('image/jpeg');
        $media->setFileName('image');
        $media->setFileExtension('jpg');
        $media->setUrl('/image.jpg');

        return $media;
    }

    private function getCategory(): CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translation');
        $criteria->setLimit(1);

        /** @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');
        $category = $categoryRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($category);

        return $category;
    }

    private function createConvertedProduct(SalesChannelProductEntity $productEntity, SalesChannelProductEntity ...$variants): Product
    {
        $uuidConverter = new UuidConverter();
        $categories = $productEntity->getCategories();
        static::assertNotNull($categories);
        $category = $categories->first();
        static::assertNotNull($category);
        $tax = $productEntity->getTax();
        static::assertNotNull($tax);
        $presentation = new Presentation();
        $presentation->setImageUrl(self::MEDIA_UPLOADED_URL);

        $product = new Product();
        $product->assign([
            'uuid' => $uuidConverter->convertUuidToV1($productEntity->getId()),
            'name' => $productEntity->getName(),
            'description' => ConstantsForTesting::PRODUCT_DESCRIPTION,
            'category' => [
                'uuid' => $uuidConverter->convertUuidToV1($category->getId()),
                'name' => $category->getName(),
            ],
            'vatPercentage' => $tax->getTaxRate(),
            'metadata' => [
                'inPos' => true,
                'source' => [
                    'external' => true,
                    'name' => SwagPayPal::POS_PARTNER_IDENTIFIER,
                ],
            ],
        ]);
        if ($productEntity->getCover() !== null) {
            $product->setPresentation($presentation);
        }

        $isWithoutVariants = \count($variants) === 1 && \current($variants) === $productEntity;
        if (!$isWithoutVariants) {
            $product->setVariantOptionDefinitions(new VariantOptionDefinitions());
        }

        foreach ($variants as $variantEntity) {
            $variant = new Variant();
            $variant->assign([
                'uuid' => $uuidConverter->convertUuidToV1($isWithoutVariants ? $uuidConverter->incrementUuid($variantEntity->getId()) : $variantEntity->getId()),
                'name' => $variantEntity->getName(),
                'description' => $variantEntity->getDescription(),
                'sku' => $variantEntity->getProductNumber(),
                'barcode' => $variantEntity->getEan(),
                'price' => [
                    'amount' => ConstantsForTesting::PRODUCT_PRICE_CONVERTED,
                    'currencyId' => 'EUR',
                ],
                'costPrice' => [
                    'amount' => ConstantsForTesting::PRODUCT_PRICE_CONVERTED * 2,
                    'currencyId' => 'EUR',
                ],
            ]);
            if ($variantEntity->getCover() !== null) {
                $variant->setPresentation($presentation);
            }
            $product->addVariant($variant);
        }

        return $product;
    }
}

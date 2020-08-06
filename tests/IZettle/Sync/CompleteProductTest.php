<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Api\Product\Presentation;
use Swag\PayPal\IZettle\Api\Product\Variant;
use Swag\PayPal\IZettle\Api\Product\VariantOptionDefinitions;
use Swag\PayPal\IZettle\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\PriceConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Converter\VariantConverter;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;
use Swag\PayPal\IZettle\MessageQueue\Handler\Sync\ProductCleanupSyncHandler;
use Swag\PayPal\IZettle\MessageQueue\Handler\Sync\ProductSingleSyncHandler;
use Swag\PayPal\IZettle\MessageQueue\Handler\Sync\ProductVariantSyncHandler;
use Swag\PayPal\IZettle\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;
use Swag\PayPal\IZettle\Sync\Context\ProductContextFactory;
use Swag\PayPal\IZettle\Sync\Product\DeletedUpdater;
use Swag\PayPal\IZettle\Sync\Product\NewUpdater;
use Swag\PayPal\IZettle\Sync\Product\OutdatedUpdater;
use Swag\PayPal\IZettle\Sync\Product\UnsyncedChecker;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Helper\SalesChannelTrait;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\CreateProductFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\DeleteProductFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\DeleteProductsFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\UpdateProductFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\IZettleClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleMediaRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleProductRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelProductRepoMock;

class CompleteProductTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private const DOMAIN = 'https://www.example.com';
    private const MEDIA_UPLOADED_URL = 'https://via.placeholder.com/500x500';
    private const MEDIA_A_ID = 'd7f9cc539a9c463ab95f15884872aad7';
    private const MEDIA_B_ID = '42e1a918149e4f599e16ca672e0f39e8';

    public function testProductSync(): void
    {
        $productResource = new ProductResource(new IZettleClientFactoryMock());
        $iZettleProductRepository = new IZettleProductRepoMock();
        $iZettleMediaRepository = new IZettleMediaRepoMock();
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
                new PresentationConverter()
            ),
            new OptionGroupConverter(),
            new PresentationConverter()
        );

        $messageBus = new MessageBusMock();

        $runService = new RunService(
            new RunRepoMock(),
            new RunLogRepoMock(),
            new Logger('test')
        );

        $productSyncer = new ProductSyncer(
            $productConverter,
            new ProductContextFactory(
                $iZettleProductRepository,
                $iZettleMediaRepository
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
                $productRepository,
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

        $productSyncManager = new ProductSyncManager($messageBus, $productSelection, $salesChannelProductRepository);

        $productVariantSyncHandler = new ProductVariantSyncHandler(
            $runService,
            new NullLogger(),
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer
        );

        $productSingleSyncHandler = new ProductSingleSyncHandler(
            $runService,
            new NullLogger(),
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer
        );

        $productCleanupSyncHandler = new ProductCleanupSyncHandler(
            $runService,
            new NullLogger(),
            $productSelection,
            $salesChannelProductRepository,
            $productSyncer
        );

        $context = Context::createDefaultContext();
        $salesChannel = $this->getSalesChannel($context);
        $tax = $this->getTax();
        $category = $this->getCategory();
        $mediaA = $this->getMedia(self::MEDIA_A_ID, 'first.jpg');
        $mediaB = $this->getMedia(self::MEDIA_B_ID, 'second.jpg');

        /*
          * A - unchanged
          * B - new (with variants A, B)
          * C - new (simple), with non-uploaded media
          * D - updated (simple)
          * E - updated (with variants C, D), with uploaded media
          * F - removed
          * G - manually added to iZettle
          */
        $productA = $salesChannelProductRepository->createMockEntity($tax, $category, 'productA', ConstantsForTesting::PRODUCT_A_ID);
        $productRepository->addMockEntity($productA);
        $productB = $salesChannelProductRepository->createMockEntity($tax, $category, 'productB', ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($productB);
        $variantA = $salesChannelProductRepository->createMockEntity($tax, $category, 'productB_variantA', ConstantsForTesting::VARIANT_A_ID, ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($variantA);
        $variantB = $salesChannelProductRepository->createMockEntity($tax, $category, 'productB_variantB', ConstantsForTesting::VARIANT_B_ID, ConstantsForTesting::PRODUCT_B_ID);
        $productRepository->addMockEntity($variantB);
        $productB->setChildCount(2);
        $productC = $salesChannelProductRepository->createMockEntity($tax, $category, 'productC', ConstantsForTesting::PRODUCT_C_ID, null, $mediaB);
        $productRepository->addMockEntity($productC);
        $productD = $salesChannelProductRepository->createMockEntity($tax, $category, 'productD', ConstantsForTesting::PRODUCT_D_ID);
        $productRepository->addMockEntity($productD);
        $productE = $salesChannelProductRepository->createMockEntity($tax, $category, 'productE', ConstantsForTesting::PRODUCT_E_ID);
        $productRepository->addMockEntity($productE);
        $variantC = $salesChannelProductRepository->createMockEntity($tax, $category, 'productE_variantC', ConstantsForTesting::VARIANT_C_ID, ConstantsForTesting::PRODUCT_E_ID, $mediaA);
        $productRepository->addMockEntity($variantC);
        $variantD = $salesChannelProductRepository->createMockEntity($tax, $category, 'productE_variantD', ConstantsForTesting::VARIANT_D_ID, ConstantsForTesting::PRODUCT_E_ID, $mediaA);
        $productRepository->addMockEntity($variantD);
        $productE->setChildCount(2);
        static::assertCount(9, $productRepository->getCollection());

        // create current checksum for unchanged product A
        $convertedGroupingA = $productConverter->convertShopwareProducts(
            new ProductCollection([$productA]),
            $salesChannel->getCurrency(),
            new ProductContext($salesChannel, new IZettleSalesChannelProductCollection([]), new IZettleSalesChannelMediaCollection([]), $context)
        )->first();
        static::assertNotNull($convertedGroupingA);

        $productStateA = $iZettleProductRepository->createMockEntity($productA, $convertedGroupingA->getProduct(), Defaults::SALES_CHANNEL);
        $productStateD = $iZettleProductRepository->createMockEntity($productD, new Product(), Defaults::SALES_CHANNEL);
        $productStateE = $iZettleProductRepository->createMockEntity($productE, new Product(), Defaults::SALES_CHANNEL);
        $deletedProductF = new ProductEntity();
        $deletedProductF->setId(ConstantsForTesting::PRODUCT_F_ID);
        $deletedProductF->setVersionId(Uuid::randomHex());
        $productStateF = $iZettleProductRepository->createMockEntity($deletedProductF, new Product(), Defaults::SALES_CHANNEL);
        static::assertCount(4, $iZettleProductRepository->getCollection());

        $mediaState = $iZettleMediaRepository->createMockEntity($mediaA, Defaults::SALES_CHANNEL, 'lookupKey', self::MEDIA_UPLOADED_URL);

        $productSyncManager->buildMessages(
            $salesChannel,
            $context,
            $runService->startRun(Defaults::SALES_CHANNEL, 'product', $context)
        );

        $messageBus->execute([
            $productSingleSyncHandler,
            $productVariantSyncHandler,
            $productCleanupSyncHandler,
        ]);

        static::assertCount(5, $iZettleProductRepository->getCollection());
        static::assertNotContains($productStateF, $iZettleProductRepository->getCollection());
        static::assertSame($convertedGroupingA->getProduct()->generateChecksum(), $productStateA->getChecksum());
        static::assertNotEquals((new Product())->generateChecksum(), $productStateD->getChecksum());
        static::assertNotEquals((new Product())->generateChecksum(), $productStateE->getChecksum());

        static::assertSame(ConstantsForTesting::PRODUCT_F_ID_CONVERTED, DeleteProductFixture::$lastDeletedUuid);
        static::assertSame([ConstantsForTesting::PRODUCT_G_ID_CONVERTED], DeleteProductsFixture::$lastDeletedUuids);

        static::assertEquals($this->createConvertedProduct($productB, $variantA, $variantB), CreateProductFixture::$lastCreatedProducts[1]);
        $productC->assign(['cover' => null]);
        static::assertEquals($this->createConvertedProduct($productC, $productC), CreateProductFixture::$lastCreatedProducts[0]);
        static::assertEquals($this->createConvertedProduct($productD, $productD), UpdateProductFixture::$lastUpdatedProducts[0]);
        static::assertEquals($this->createConvertedProduct($productE, $variantC, $variantD), UpdateProductFixture::$lastUpdatedProducts[1]);
        static::assertCount(2, CreateProductFixture::$lastCreatedProducts);
        static::assertCount(2, UpdateProductFixture::$lastUpdatedProducts);

        static::assertCount(2, $iZettleMediaRepository->getCollection());
        static::assertContains($mediaState, $iZettleMediaRepository->getCollection());
    }

    private function getTax(): TaxEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        /** @var EntityRepositoryInterface $taxRepository */
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

        /** @var EntityRepositoryInterface $categoryRepository */
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

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Util;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Exception\PosTokenException;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\Exception\ExistingPosAccountException;
use Swag\PayPal\Pos\MessageQueue\Handler\CloneVisibilityHandler;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Pos\Resource\UserResource;
use Swag\PayPal\Pos\Setting\Service\ApiCredentialService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Service\InformationFetchService;
use Swag\PayPal\Pos\Setting\Service\ProductCountService;
use Swag\PayPal\Pos\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\Pos\Setting\SettingsController;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
use Swag\PayPal\Pos\Setting\Struct\ProductCount;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\GetProductCountFixture;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\ProductVisibilityRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;
use Symfony\Component\HttpFoundation\Request;

class SettingsControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const FROM_SALES_CHANNEL = 'salesChannelA';
    private const TO_SALES_CHANNEL = 'salesChannelB';
    private const LOCAL_PRODUCT_COUNT = 5;

    private ProductVisibilityRepoMock $productVisibilityRepository;

    private MessageBusMock $messageBus;

    private SalesChannelProductRepoMock $salesChannelProductRepository;

    private SalesChannelRepoMock $salesChannelRepository;

    private RunRepoMock $runRepository;

    private RunServiceMock $runService;

    public function testValidateCredentialsValid(): void
    {
        $response = $this->getSettingsController(false)->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
        ]), Context::createDefaultContext());
        static::assertSame($response->getContent(), \json_encode(['credentialsValid' => true]));
    }

    public function testValidateCredentialsInvalid(): void
    {
        $this->expectException(PosTokenException::class);
        $this->getSettingsController(false)->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::INVALID_API_KEY,
        ]), Context::createDefaultContext());
    }

    public function testValidateCredentialsDuplicate(): void
    {
        $this->expectException(ExistingPosAccountException::class);
        $this->getSettingsController()->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
        ]), Context::createDefaultContext());
    }

    public function testValidateCredentialsDuplicateSameSalesChannel(): void
    {
        $settingsController = $this->getSettingsController();
        $this->salesChannelRepository->getCollection()->remove($this->salesChannelRepository->getMockEntityWithNoTypeId()->getId());
        $this->salesChannelRepository->getCollection()->remove($this->salesChannelRepository->getMockInactiveEntity()->getId());

        $response = $settingsController->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
            'salesChannelId' => $this->salesChannelRepository->getMockEntity()->getId(),
        ]), Context::createDefaultContext());
        static::assertSame($response->getContent(), \json_encode(['credentialsValid' => true]));
    }

    public function testFetchInformation(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->getSettingsController()->fetchInformation(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
        ]), $context);
        static::assertSame($response->getContent(), \json_encode($this->createExpectedFetchedInformation($context)));
    }

    public function testCloneProductVisibility(): void
    {
        $context = Context::createDefaultContext();
        $settingsController = $this->getSettingsController();

        $this->salesChannelRepository->getMockEntityWithNoTypeId()->setId(self::TO_SALES_CHANNEL);
        $this->salesChannelRepository->addMockEntity($this->salesChannelRepository->getMockEntityWithNoTypeId());

        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::TO_SALES_CHANNEL, 30);

        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::FROM_SALES_CHANNEL));
        static::assertCount(1, $this->productVisibilityRepository->filterBySalesChannelId(self::TO_SALES_CHANNEL));

        $settingsController->cloneProductVisibility(new Request([], [
            'fromSalesChannelId' => self::FROM_SALES_CHANNEL,
            'toSalesChannelId' => self::TO_SALES_CHANNEL,
        ]), $context);

        $messageDispatcher = new MessageDispatcher($this->messageBus, $this->createMock(Connection::class));
        $messageHydrator = new MessageHydrator($this->createMock(SalesChannelContextService::class), $this->salesChannelRepository);

        $this->messageBus->execute([
            new CloneVisibilityHandler(
                $this->runService,
                new Logger('test'),
                $messageDispatcher,
                $messageHydrator,
                $this->productVisibilityRepository
            ),
            new SyncManagerHandler(
                $messageDispatcher,
                $messageHydrator,
                $this->runService,
                new NullLogger(),
                $this->createMock(ImageSyncManager::class),
                $this->createMock(InventorySyncManager::class),
                $this->createMock(ProductSyncManager::class)
            ),
        ]);

        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::FROM_SALES_CHANNEL));
        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::TO_SALES_CHANNEL));

        /** @var PosSalesChannelRunEntity|null $run */
        $run = $this->runRepository->search(new Criteria(), $context)->first();
        static::assertNotNull($run);
        static::assertSame(PosSalesChannelRunDefinition::STATUS_FINISHED, $run->getStatus());
    }

    public function testProductCount(): void
    {
        $context = Context::createDefaultContext();
        $settingsController = $this->getSettingsController();

        for ($i = 0; $i < self::LOCAL_PRODUCT_COUNT * 2; ++$i) {
            $product = new SalesChannelProductEntity();
            $product->setId(Uuid::randomHex());
            $product->setVersionId(Uuid::randomHex());
            if ($i % 2 === 0) {
                $product->setParentId(Uuid::randomHex());
            }
            $this->salesChannelProductRepository->addMockEntity($product);
        }

        $this->salesChannelRepository->getMockEntityWithNoTypeId()->setId(TestDefaults::SALES_CHANNEL);
        $this->salesChannelRepository->addMockEntity($this->salesChannelRepository->getMockEntityWithNoTypeId());

        $request = new Request([
            'salesChannelId' => $this->salesChannelRepository->getMockEntity()->getId(),
            'cloneSalesChannelId' => $this->salesChannelRepository->getMockEntityWithNoTypeId()->getId(),
        ]);

        $response = $settingsController->getProductCounts($request, $context);

        $expected = new ProductCount();
        $expected->setLocalCount(self::LOCAL_PRODUCT_COUNT);
        $expected->setRemoteCount(GetProductCountFixture::PRODUCT_COUNT);

        static::assertSame(\json_encode($expected), $response->getContent());
    }

    public function testProductCountNoClone(): void
    {
        $context = Context::createDefaultContext();
        $settingsController = $this->getSettingsController();

        $request = new Request([
            'salesChannelId' => $this->salesChannelRepository->getMockEntity()->getId(),
        ]);

        $response = $settingsController->getProductCounts($request, $context);

        $expected = new ProductCount();
        $expected->setLocalCount(0);
        $expected->setRemoteCount(GetProductCountFixture::PRODUCT_COUNT);

        static::assertSame(\json_encode($expected), $response->getContent());
    }

    private function getSettingsController(bool $withSalesChannels = true): SettingsController
    {
        /** @var EntityRepository $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');
        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');
        /** @var EntityRepository $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');
        /** @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');
        /** @var EntityRepository $shippingMethodRepository */
        $shippingMethodRepository = $this->getContainer()->get('shipping_method.repository');
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        /** @var EntityRepository $deliveryTimeRepository */
        $deliveryTimeRepository = $this->getContainer()->get('delivery_time.repository');
        /** @var EntityRepository $ruleRepository */
        $ruleRepository = $this->getContainer()->get('rule.repository');

        $this->productVisibilityRepository = new ProductVisibilityRepoMock();
        $this->messageBus = new MessageBusMock();
        $this->runRepository = new RunRepoMock();
        $this->runService = new RunServiceMock(
            $this->runRepository,
            new RunLogRepoMock(),
            $this->createMock(Connection::class),
            new Logger('test')
        );

        $this->salesChannelProductRepository = new SalesChannelProductRepoMock();
        $this->salesChannelRepository = new SalesChannelRepoMock();
        $messageDispatcher = new MessageDispatcher($this->messageBus, $this->createMock(Connection::class));

        if (!$withSalesChannels) {
            $this->salesChannelRepository->getCollection()->clear();
        }

        return new SettingsController(
            new ApiCredentialService(
                new TokenResource(
                    new CacheMock(),
                    new TokenClientFactoryMock()
                ),
                $this->salesChannelRepository,
                new ApiKeyDecoder()
            ),
            new InformationFetchService(
                new UserResource(new PosClientFactoryMock()),
                $countryRepository,
                $currencyRepository,
                $languageRepository
            ),
            new InformationDefaultService(
                $customerGroupRepository,
                $categoryRepository,
                $this->getContainer()->get(PluginIdProvider::class),
                $paymentMethodRepository,
                $ruleRepository,
                $deliveryTimeRepository,
                $shippingMethodRepository
            ),
            new ProductVisibilityCloneService(
                $messageDispatcher,
                $this->productVisibilityRepository,
                $this->runService,
                $this->salesChannelRepository
            ),
            new ProductCountService(
                new ProductResource(new PosClientFactoryMock()),
                new ProductSelection(
                    $this->salesChannelProductRepository,
                    $this->createMock(ProductStreamBuilder::class),
                    $this->getContainer()->get(SalesChannelContextFactory::class)
                ),
                $this->salesChannelProductRepository,
                $this->salesChannelRepository
            )
        );
    }

    private function createExpectedFetchedInformation(Context $context): AdditionalInformation
    {
        $countryCriteria = new Criteria();
        $countryCriteria->addFilter(new EqualsFilter('iso', 'DE'));
        $countryCriteria->setLimit(1);
        /** @var EntityRepository $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(new EqualsFilter('name', 'Deutsch'));
        $languageCriteria->setLimit(1);
        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        $customerGroupCriteria = new Criteria();
        $customerGroupCriteria->addFilter(new EqualsFilter('displayGross', true));
        $customerGroupCriteria->addSorting(new FieldSorting('createdAt'));
        $customerGroupCriteria->setLimit(1);
        /** @var EntityRepository $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');

        $categoryCriteria = new Criteria();
        $categoryCriteria->addFilter(new EqualsFilter('parentId', null));
        $categoryCriteria->addSorting(new FieldSorting('createdAt'));
        $categoryCriteria->setLimit(1);
        /** @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        $shippingMethodCriteria = new Criteria([InformationDefaultService::POS_SHIPPING_METHOD_ID]);
        /** @var EntityRepository $shippingMethodRepository */
        $shippingMethodRepository = $this->getContainer()->get('shipping_method.repository');

        $paymentMethodCriteria = new Criteria([InformationDefaultService::POS_PAYMENT_METHOD_ID]);
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        $expected = new AdditionalInformation();
        $expected->assign([
            'countryId' => $countryRepository->searchIds($countryCriteria, $context)->firstId(),
            'currencyId' => Defaults::CURRENCY,
            'languageId' => $languageRepository->searchIds($languageCriteria, $context)->firstId(),
            'customerGroupId' => $customerGroupRepository->searchIds($customerGroupCriteria, $context)->firstId(),
            'navigationCategoryId' => $categoryRepository->searchIds($categoryCriteria, $context)->firstId(),
            'shippingMethodId' => $shippingMethodRepository->searchIds($shippingMethodCriteria, $context)->firstId(),
            'paymentMethodId' => $paymentMethodRepository->searchIds($paymentMethodCriteria, $context)->firstId(),
            'merchantInformation' => [
                'name' => 'Max Mustermann',
                'contactEmail' => 'someone@somewhere.com',
            ],
        ]);

        return $expected;
    }
}

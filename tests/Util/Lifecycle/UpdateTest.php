<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\RestrictDeleteViolationException;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldDefinition;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\WebhookRegistry;
use Swag\PayPal\Pos\Webhook\WebhookService as PosWebhookService;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext as ApplicationContextV1;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext as ApplicationContextV2;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookUpdateFixture;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Util\Lifecycle\Installer\MediaInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;
use Swag\PayPal\Util\Lifecycle\Update;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\Routing\Router;

class UpdateTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;
    use SalesChannelTrait;
    use PosSalesChannelTrait;

    private const CLIENT_ID = 'testClientId';
    private const CLIENT_SECRET = 'testClientSecret';
    private const OTHER_CLIENT_ID = 'someOtherTestClientId';
    private const OTHER_CLIENT_SECRET = 'someOtherTestClientSecret';

    private PaymentMethodRepositoryDecorator $paymentMethodRepository;

    private EntityRepositoryInterface $salesChannelRepository;

    protected function setUp(): void
    {
        /** @var PaymentMethodRepositoryDecorator $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get(\sprintf('%s.repository', PaymentMethodDefinition::ENTITY_NAME));
        $this->paymentMethodRepository = $paymentMethodRepository;
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get(\sprintf('%s.repository', SalesChannelDefinition::ENTITY_NAME));
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function testUpdateTo130WithNoPreviousSettings(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock();
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertNull($systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertNull($systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
    }

    public function testUpdateTo130WithSandboxEnabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => self::CLIENT_ID,
            Settings::CLIENT_SECRET => self::CLIENT_SECRET,
            Settings::SANDBOX => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame('', $systemConfigService->get(Settings::CLIENT_ID));
        static::assertSame('', $systemConfigService->get(Settings::CLIENT_SECRET));
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
    }

    public function testUpdateTo130WithSandboxDisabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => self::CLIENT_ID,
            Settings::CLIENT_SECRET => self::CLIENT_SECRET,
            Settings::SANDBOX => false,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(Settings::CLIENT_ID));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(Settings::CLIENT_SECRET));
        static::assertNull($systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertNull($systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
    }

    public function testUpdateTo130WithSandboxSettingsSet(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => self::CLIENT_ID,
            Settings::CLIENT_SECRET => self::CLIENT_SECRET,
            Settings::CLIENT_ID_SANDBOX => self::OTHER_CLIENT_ID,
            Settings::CLIENT_SECRET_SANDBOX => self::OTHER_CLIENT_SECRET,
            Settings::SANDBOX => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::OTHER_CLIENT_ID, $systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_SECRET, $systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
    }

    public function testUpdateTo170(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID_SANDBOX => self::OTHER_CLIENT_ID,
            Settings::CLIENT_SECRET_SANDBOX => self::OTHER_CLIENT_SECRET,
            Settings::SANDBOX => true,
        ]);
        $updateContext = $this->createUpdateContext('1.6.9', '1.7.0');
        $update = $this->createUpdateService($systemConfigService, $this->createWebhookService($systemConfigService));
        $update->update($updateContext);
        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $systemConfigService->get(Settings::WEBHOOK_ID));
    }

    public function testUpdateTo170WithMissingSettings(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock();
        $updateContext = $this->createUpdateContext('1.6.9', '1.7.0');
        $update = $this->createUpdateService($systemConfigService, $this->createWebhookService($systemConfigService));
        $update->update($updateContext);
        static::assertNull($systemConfigService->get(Settings::WEBHOOK_ID));
    }

    public function testUpdateTo172(): void
    {
        $updateContext = $this->createUpdateContext('1.7.1', '1.7.2');

        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->getContainer()->get((new CustomFieldDefinition())->getEntityName() . '.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID));
        $context = $updateContext->getContext();

        $customFieldIds = $customFieldRepository->searchIds($criteria, $context);
        if ($customFieldIds->getTotal() !== 0) {
            $data = \array_map(static function ($id) {
                return ['id' => $id];
            }, $customFieldIds->getIds());
            $customFieldRepository->delete($data, $context);
        }

        $customFieldRepository->create(
            [
                [
                    'name' => SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID,
                    'type' => CustomFieldTypes::TEXT,
                ],
            ],
            $context
        );

        $update = $this->createUpdateService($this->createSystemConfigServiceMock());
        $update->update($updateContext);

        static::assertEquals(0, $customFieldRepository->searchIds($criteria, $context)->getTotal());
    }

    public function testUpdateTo200ChangePaymentHandlerIdentifier(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $context = $updateContext->getContext();

        $paypalId = Uuid::randomHex();

        $this->paymentMethodRepository->create([
            [
                'id' => $paypalId,
                'handlerIdentifier' => 'Swag\PayPal\Payment\PayPalPaymentHandler',
                'name' => 'Test old PayPal payment handler',
            ],
        ], $context);

        $updater = $this->createUpdateService($this->createSystemConfigServiceMock());
        $updater->update($updateContext);

        /** @var PaymentMethodEntity|null $updatedPaymentMethod */
        $updatedPaymentMethod = $this->paymentMethodRepository->search(new Criteria([$paypalId]), $context)->first();
        static::assertNotNull($updatedPaymentMethod);
        static::assertSame(PayPalPaymentHandler::class, $updatedPaymentMethod->getHandlerIdentifier());
    }

    public function testUpdateTo200MigrateSettings(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();

        $systemConfig->set(Settings::INTENT, PaymentIntentV1::SALE);
        $systemConfig->set(Settings::INTENT, PaymentIntentV1::ORDER, Defaults::SALES_CHANNEL);

        $systemConfig->set(Settings::LANDING_PAGE, ApplicationContextV1::LANDING_PAGE_TYPE_LOGIN);
        $systemConfig->set(Settings::LANDING_PAGE, ApplicationContextV1::LANDING_PAGE_TYPE_BILLING, Defaults::SALES_CHANNEL);

        $updater = $this->createUpdateService($systemConfig);
        $updater->update($updateContext);

        static::assertSame(PaymentIntentV2::CAPTURE, $systemConfig->get(Settings::INTENT, null, false));
        static::assertSame(PaymentIntentV2::AUTHORIZE, $systemConfig->get(Settings::INTENT, Defaults::SALES_CHANNEL, false));
        static::assertSame(ApplicationContextV2::LANDING_PAGE_TYPE_LOGIN, $systemConfig->get(Settings::LANDING_PAGE, null, false));
        static::assertSame(ApplicationContextV2::LANDING_PAGE_TYPE_BILLING, $systemConfig->get(Settings::LANDING_PAGE, Defaults::SALES_CHANNEL, false));
    }

    public function testUpdateTo200MigrateIntentSettingWithInvalidIntent(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();
        $systemConfig->set(Settings::INTENT, 'invalidIntent');

        $updater = $this->createUpdateService($systemConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for "SwagPayPal.settings.intent" setting');
        $updater->update($updateContext);
    }

    public function testUpdateTo200MigrateIntentSettingWithInvalidLandingPage(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();

        $systemConfig->set(Settings::LANDING_PAGE, 'invalidLandingPage');

        $updater = $this->createUpdateService($systemConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for "SwagPayPal.settings.landingPage" setting');
        $updater->update($updateContext);
    }

    public function testUpdateTo300(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID_SANDBOX => self::OTHER_CLIENT_ID,
            Settings::CLIENT_SECRET_SANDBOX => self::OTHER_CLIENT_SECRET,
            Settings::SANDBOX => true,
            Settings::WEBHOOK_ID => 'anyIdWillDo',
        ]);

        $updateContext = $this->createUpdateContext('2.2.2', '3.0.0');
        $update = $this->createUpdateService(
            $systemConfigService,
            $this->createWebhookService($systemConfigService),
            $this->createPosWebhookService($systemConfigService)
        );

        $salesChannel = $this->getSalesChannel($updateContext->getContext());
        $this->salesChannelRepository->update([[
            'id' => Defaults::SALES_CHANNEL,
            'typeId' => SwagPayPal::SALES_CHANNEL_TYPE_POS,
            SwagPayPal::SALES_CHANNEL_POS_EXTENSION => \array_filter($this->getPosSalesChannel($salesChannel)->jsonSerialize()),
        ]], $updateContext->getContext());
        $systemConfigService->set('core.basicInformation.email', 'some@one.com', $salesChannel->getId());

        $update->update($updateContext);
        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $systemConfigService->get(Settings::WEBHOOK_ID));
        static::assertTrue(WebhookUpdateFixture::$sent);
    }

    public function testUpdateToREPLACEGLOBALLYWITHNEXTVERSIONChangePaymentHandlerIdentifier(): void
    {
        $updateContext = $this->createUpdateContext('1.0.0', '4.9.0');
        $context = $updateContext->getContext();

        $paypalPuiId = Uuid::randomHex();

        $this->paymentMethodRepository->create([
            [
                'id' => $paypalPuiId,
                'handlerIdentifier' => 'Swag\PayPal\Payment\PayPalPuiPaymentHandler',
                'name' => 'Test old PayPal PUI payment handler',
            ],
        ], $context);

        $updater = $this->createUpdateService($this->createSystemConfigServiceMock());
        $updater->update($updateContext);

        /** @var PaymentMethodEntity|null $updatedPaymentMethod */
        $updatedPaymentMethod = $this->paymentMethodRepository->search(new Criteria([$paypalPuiId]), $context)->first();
        static::assertNotNull($updatedPaymentMethod);
        static::assertSame(PUIHandler::class, $updatedPaymentMethod->getHandlerIdentifier());
    }

    public function testUpdateToREPLACEGLOBALLYWITHNEXTVERSIONCreatesNewPaymentMethod(): void
    {
        $updateContext = $this->createUpdateContext('4.1.0', '4.9.0');
        $context = $updateContext->getContext();

        $criteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', ACDCHandler::class));
        $acdcPaymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($acdcPaymentMethodId);

        try {
            $this->paymentMethodRepository->internalDelete([[
                'id' => $acdcPaymentMethodId,
            ]], $context);
        } catch (RestrictDeleteViolationException $e) {
            static::markTestSkipped('Could not delete payment method, probably orders exist');
        }

        $updater = $this->createUpdateService($this->createSystemConfigServiceMock());
        $updater->update($updateContext);

        $acdcPaymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($acdcPaymentMethodId);
    }

    private function createUpdateContext(string $currentPluginVersion, string $nextPluginVersion): UpdateContext
    {
        /** @var MigrationCollectionLoader $migrationLoader */
        $migrationLoader = $this->getContainer()->get(MigrationCollectionLoader::class);

        return new UpdateContext(
            new SwagPayPal(true, ''),
            Context::createDefaultContext(),
            '',
            $currentPluginVersion,
            $migrationLoader->collect('core'),
            $nextPluginVersion
        );
    }

    private function createUpdateService(
        SystemConfigServiceMock $systemConfigService,
        ?WebhookService $webhookService = null,
        ?PosWebhookService $posWebhookService = null
    ): Update {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->getContainer()->get(CustomFieldDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->getContainer()->get(RuleDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $ruleConditionRepository */
        $ruleConditionRepository = $this->getContainer()->get(RuleConditionDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $salesChannelTypeRepository */
        $salesChannelTypeRepository = $this->getContainer()->get(SalesChannelTypeDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $mediaRepository */
        $mediaRepository = $this->getContainer()->get(MediaDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $mediaFolderRepository */
        $mediaFolderRepository = $this->getContainer()->get(MediaFolderDefinition::ENTITY_NAME . '.repository');
        /** @var InformationDefaultService|null $informationDefaultService */
        $informationDefaultService = $this->getContainer()->get(InformationDefaultService::class);
        /** @var EntityRepositoryInterface $shippingRepository */
        $shippingRepository = $this->getContainer()->get('shipping_method.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->getContainer()->get(PluginIdProvider::class);
        /** @var FileSaver $fileSaver */
        $fileSaver = $this->getContainer()->get(FileSaver::class);
        $paymentMethodDataRegistry = new PaymentMethodDataRegistry($this->paymentMethodRepository, $this->getContainer());

        return new Update(
            $systemConfigService,
            $this->paymentMethodRepository,
            $customFieldRepository,
            $webhookService,
            $this->salesChannelRepository,
            $salesChannelTypeRepository,
            $informationDefaultService,
            $shippingRepository,
            $posWebhookService,
            new PaymentMethodInstaller(
                $this->paymentMethodRepository,
                $ruleRepository,
                $ruleConditionRepository,
                $pluginIdProvider,
                $paymentMethodDataRegistry,
                new MediaInstaller(
                    $mediaRepository,
                    $mediaFolderRepository,
                    $this->paymentMethodRepository,
                    $fileSaver,
                ),
            ),
            new PaymentMethodStateService(
                $paymentMethodDataRegistry,
                $this->paymentMethodRepository,
            )
        );
    }

    private function createWebhookService(SystemConfigServiceMock $systemConfigService): WebhookService
    {
        return new WebhookService(
            new WebhookResource($this->createPayPalClientFactoryWithService($systemConfigService)),
            $this->createWebhookRegistry(new OrderTransactionRepoMock()),
            $systemConfigService,
            new RouterMock()
        );
    }

    private function createPosWebhookService(SystemConfigServiceMock $systemConfigService): PosWebhookService
    {
        $webhookRegistry = new WebhookRegistry(new \ArrayObject([]));
        /** @var Router $router */
        $router = $this->getContainer()->get('router');

        return new PosWebhookService(
            new SubscriptionResource(new PosClientFactoryMock()),
            $webhookRegistry,
            $this->salesChannelRepository,
            $systemConfigService,
            new UuidConverter(),
            $router
        );
    }
}

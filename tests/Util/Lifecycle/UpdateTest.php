<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldDefinition;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext as ApplicationContextV1;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext as ApplicationContextV2;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\Lifecycle\Update;
use Swag\PayPal\Webhook\WebhookService;

class UpdateTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

    private const CLIENT_ID = 'testClientId';
    private const CLIENT_SECRET = 'testClientSecret';
    private const OTHER_CLIENT_ID = 'someOtherTestClientId';
    private const OTHER_CLIENT_SECRET = 'someOtherTestClientSecret';

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function testUpdateTo130WithNoPreviousSettings(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock();
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxEnabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame('', $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'));
        static::assertSame('', $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'));
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxDisabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => false,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxSettingsSet(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox' => self::OTHER_CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox' => self::OTHER_CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = $this->createUpdateService($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::OTHER_CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertSame(self::OTHER_CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo170(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox' => self::OTHER_CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox' => self::OTHER_CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
        ]);
        $updateContext = $this->createUpdateContext('1.6.9', '1.7.0');
        $update = $this->createUpdateService($systemConfigService, $this->createWebhookService($systemConfigService));
        $update->update($updateContext);
        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'webhookId'));
    }

    public function testUpdateTo170WithMissingSettings(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock();
        $updateContext = $this->createUpdateContext('1.6.9', '1.7.0');
        $update = $this->createUpdateService($systemConfigService, $this->createWebhookService($systemConfigService));
        $update->update($updateContext);
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'webhookId'));
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
        $paypalPuiId = Uuid::randomHex();

        $this->paymentMethodRepository->create([
            [
                'id' => $paypalId,
                'handlerIdentifier' => 'Swag\PayPal\Payment\PayPalPaymentHandler',
                'name' => 'Test old PayPal payment handler',
            ],
            [
                'id' => $paypalPuiId,
                'handlerIdentifier' => 'Swag\PayPal\Payment\PayPalPuiPaymentHandler',
                'name' => 'Test old PayPal PUI payment handler',
            ],
        ], $context);

        $updater = $this->createUpdateService($this->createSystemConfigServiceMock());
        $updater->update($updateContext);

        /** @var PaymentMethodCollection $updatedPaymentMethods */
        $updatedPaymentMethods = $this->paymentMethodRepository->search(new Criteria([$paypalId, $paypalPuiId]), $context)->getEntities();
        foreach ($updatedPaymentMethods as $updatedPaymentMethod) {
            if ($updatedPaymentMethod->getId() === $paypalId) {
                static::assertSame(PayPalPaymentHandler::class, $updatedPaymentMethod->getHandlerIdentifier());

                continue;
            }
            static::assertSame(PayPalPuiPaymentHandler::class, $updatedPaymentMethod->getHandlerIdentifier());
        }
    }

    public function testUpdateTo200MigrateSettings(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();

        $settingKeyIntent = SettingsService::SYSTEM_CONFIG_DOMAIN . 'intent';
        $systemConfig->set($settingKeyIntent, PaymentIntentV1::SALE);
        $systemConfig->set($settingKeyIntent, PaymentIntentV1::ORDER, Defaults::SALES_CHANNEL);

        $settingKeyLandingPage = SettingsService::SYSTEM_CONFIG_DOMAIN . 'landingPage';
        $systemConfig->set($settingKeyLandingPage, ApplicationContextV1::LANDING_PAGE_TYPE_LOGIN);
        $systemConfig->set($settingKeyLandingPage, ApplicationContextV1::LANDING_PAGE_TYPE_BILLING, Defaults::SALES_CHANNEL);

        $updater = $this->createUpdateService($systemConfig);
        $updater->update($updateContext);

        static::assertSame(PaymentIntentV2::CAPTURE, $systemConfig->get($settingKeyIntent, null, false));
        static::assertSame(PaymentIntentV2::AUTHORIZE, $systemConfig->get($settingKeyIntent, Defaults::SALES_CHANNEL, false));
        static::assertSame(ApplicationContextV2::LANDING_PAGE_TYPE_LOGIN, $systemConfig->get($settingKeyLandingPage, null, false));
        static::assertSame(ApplicationContextV2::LANDING_PAGE_TYPE_BILLING, $systemConfig->get($settingKeyLandingPage, Defaults::SALES_CHANNEL, false));
    }

    public function testUpdateTo200MigrateIntentSettingWithInvalidIntent(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();

        $settingKeyIntent = SettingsService::SYSTEM_CONFIG_DOMAIN . 'intent';

        $systemConfig->set($settingKeyIntent, 'invalidIntent');

        $updater = $this->createUpdateService($systemConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for "SwagPayPal.settings.intent" setting');
        $updater->update($updateContext);
    }

    public function testUpdateTo200MigrateIntentSettingWithInvalidLandingPage(): void
    {
        $updateContext = $this->createUpdateContext('1.9.1', '2.0.0');
        $systemConfig = $this->createSystemConfigServiceMock();

        $settingKeyIntent = SettingsService::SYSTEM_CONFIG_DOMAIN . 'landingPage';

        $systemConfig->set($settingKeyIntent, 'invalidLandingPage');

        $updater = $this->createUpdateService($systemConfig);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid value for "SwagPayPal.settings.landingPage" setting');
        $updater->update($updateContext);
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

    private function createUpdateService(SystemConfigServiceMock $systemConfigService, ?WebhookService $webhookService = null): Update
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->getContainer()->get(CustomFieldDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get(SalesChannelDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $salesChannelTypeRepository */
        $salesChannelTypeRepository = $this->getContainer()->get(SalesChannelTypeDefinition::ENTITY_NAME . '.repository');
        /** @var InformationDefaultService|null $informationDefaultService */
        $informationDefaultService = $this->getContainer()->get(InformationDefaultService::class);
        /** @var EntityRepositoryInterface $shippingRepository */
        $shippingRepository = $this->getContainer()->get('shipping_method.repository');

        return new Update(
            $systemConfigService,
            $this->paymentMethodRepository,
            $customFieldRepository,
            $webhookService,
            $salesChannelRepository,
            $salesChannelTypeRepository,
            $informationDefaultService,
            $shippingRepository
        );
    }

    private function createWebhookService(SystemConfigServiceMock $systemConfigService): WebhookService
    {
        $settingsService = new SettingsService($systemConfigService);

        return new WebhookService(
            new WebhookResource($this->createPayPalClientFactoryWithService($settingsService)),
            $this->createWebhookRegistry(new OrderTransactionRepoMock()),
            $settingsService,
            new RouterMock()
        );
    }
}

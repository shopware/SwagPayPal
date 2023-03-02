<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Shopware\Core\System\SystemConfig\Service\ConfigurationService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigController;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class WebhookSystemConfigControllerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

    private const OTHER_CLIENT_ID = 'otherClientId';
    private const OTHER_CLIENT_SECRET = 'otherClientSecret';

    private ConfigurationService $configurationService;

    private SystemConfigService $systemConfigService;

    private SystemConfigController $undecoratedController;

    private WebhookServiceMock $webhookService;

    protected function setUp(): void
    {
        $this->configurationService = $this->getContainer()->get(ConfigurationService::class);
        $this->systemConfigService = $this->getContainer()->get(SystemConfigService::class);

        // creating new instance without decoration
        if (\class_exists(SystemConfigValidator::class)) {
            $this->undecoratedController = new SystemConfigController($this->configurationService, $this->systemConfigService, $this->getContainer()->get(SystemConfigValidator::class));
        } else {
            // @phpstan-ignore-next-line
            $this->undecoratedController = new SystemConfigController($this->configurationService, $this->systemConfigService);
        }

        $this->webhookService = new WebhookServiceMock($this->systemConfigService);
    }

    public function testBatchSaveWithChangedSandboxMode(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig['null'][Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][Settings::SANDBOX] = false;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::SANDBOX] = false;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertFalse($this->systemConfigService->get(Settings::SANDBOX));
        static::assertFalse($this->systemConfigService->get(Settings::SANDBOX, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveWithChangedSandboxCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig[TestDefaults::SALES_CHANNEL][Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][Settings::CLIENT_ID_SANDBOX] = self::OTHER_CLIENT_ID;
        $newConfig['null'][Settings::CLIENT_SECRET_SANDBOX] = self::OTHER_CLIENT_SECRET;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_ID_SANDBOX] = self::OTHER_CLIENT_ID;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_SECRET_SANDBOX] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX, TestDefaults::SALES_CHANNEL));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveWithChangedMixedCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig[TestDefaults::SALES_CHANNEL][Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][Settings::CLIENT_ID_SANDBOX] = self::OTHER_CLIENT_ID;
        $newConfig['null'][Settings::CLIENT_SECRET_SANDBOX] = self::OTHER_CLIENT_SECRET;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::SANDBOX] = false;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_ID] = self::OTHER_CLIENT_ID;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_SECRET] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID, TestDefaults::SALES_CHANNEL));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveWithChangedRegularCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig['null'][Settings::WEBHOOK_ID] = null;
        $oldConfig[TestDefaults::SALES_CHANNEL][Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][Settings::CLIENT_ID] = self::OTHER_CLIENT_ID;
        $newConfig['null'][Settings::CLIENT_SECRET] = self::OTHER_CLIENT_SECRET;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_ID] = self::OTHER_CLIENT_ID;
        $newConfig[TestDefaults::SALES_CHANNEL][Settings::CLIENT_SECRET] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET));
        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID, TestDefaults::SALES_CHANNEL));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveNoChanges(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $newConfig = $this->getDefaultConfig();

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedSandboxMode(): void
    {
        $oldConfig = $this->getDefaultConfig()[TestDefaults::SALES_CHANNEL];
        $newConfig = $this->getDefaultConfig()[TestDefaults::SALES_CHANNEL];
        $newConfig[Settings::SANDBOX] = false;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, TestDefaults::SALES_CHANNEL));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, TestDefaults::SALES_CHANNEL));

        static::assertFalse($this->systemConfigService->get(Settings::SANDBOX, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL], $this->webhookService->getRegistrations());
    }

    public function testSaveWithRemovedSalesChannelSettings(): void
    {
        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($this->getDefaultConfig()));
        $newConfig = [
            Settings::CLIENT_ID_SANDBOX => null,
            Settings::CLIENT_SECRET_SANDBOX => null,
        ];

        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, TestDefaults::SALES_CHANNEL));

        // going back to inherited config
        static::assertNotNull($this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX, TestDefaults::SALES_CHANNEL));
        static::assertNotNull($this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL], $this->webhookService->getDeregistrations());
        static::assertEmpty($this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedSandboxCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig()['null'];
        $oldConfig[Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig()['null'];
        $newConfig[Settings::CLIENT_ID_SANDBOX] = self::OTHER_CLIENT_ID;
        $newConfig[Settings::CLIENT_SECRET_SANDBOX] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, null));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, null));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX));

        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedRegularCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig()[TestDefaults::SALES_CHANNEL];
        $oldConfig[Settings::WEBHOOK_ID] = null;
        $newConfig = $this->getDefaultConfig()[TestDefaults::SALES_CHANNEL];
        $newConfig[Settings::CLIENT_ID] = self::OTHER_CLIENT_ID;
        $newConfig[Settings::CLIENT_SECRET] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, TestDefaults::SALES_CHANNEL));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, TestDefaults::SALES_CHANNEL));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(Settings::CLIENT_ID, TestDefaults::SALES_CHANNEL));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(Settings::CLIENT_SECRET, TestDefaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([TestDefaults::SALES_CHANNEL], $this->webhookService->getRegistrations());
    }

    public function testSaveNoChanges(): void
    {
        $oldConfig = $this->getDefaultConfig()['null'];
        $newConfig = $this->getDefaultConfig()['null'];

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, null));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, null));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getRegistrations());
    }

    public function testSandboxToggleWithoutSettings(): void
    {
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest(['sandbox' => true], TestDefaults::SALES_CHANNEL));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest(['sandbox' => false], TestDefaults::SALES_CHANNEL));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEmpty($this->webhookService->getRegistrations());
    }

    private function createWebhookSystemConfigController(): WebhookSystemConfigController
    {
        if (!\class_exists(SystemConfigValidator::class)) {
            // @phpstan-ignore-next-line
            return new WebhookSystemConfigController(
                $this->configurationService,
                $this->systemConfigService,
                new WebhookSystemConfigHelper(
                    new NullLogger(),
                    $this->webhookService,
                    $this->systemConfigService,
                    new SettingsValidationService($this->systemConfigService, new NullLogger())
                )
            );
        }

        return new WebhookSystemConfigController(
            $this->configurationService,
            $this->systemConfigService,
            new WebhookSystemConfigHelper(
                new NullLogger(),
                $this->webhookService,
                $this->systemConfigService,
                new SettingsValidationService($this->systemConfigService, new NullLogger())
            ),
            $this->getContainer()->get(SystemConfigValidator::class)
        );
    }

    /**
     * @param mixed[][] $config
     */
    private function createBatchRequest(array $config): Request
    {
        $request = new Request();
        $request->request->add($config);

        return $request;
    }

    /**
     * @param mixed[] $config
     */
    private function createSingleRequest(array $config, ?string $salesChannelId): Request
    {
        $request = new Request();
        $request->request->add($config);
        $request->query->set('salesChannelId', $salesChannelId);

        return $request;
    }

    /**
     * @return mixed[][]
     */
    private function getDefaultConfig(): array
    {
        return [
            'null' => [
                Settings::CLIENT_ID => 'oldClientId',
                Settings::CLIENT_SECRET => 'oldClientSecret',
                Settings::SANDBOX => true,
                Settings::CLIENT_ID_SANDBOX => 'oldClientId',
                Settings::CLIENT_SECRET_SANDBOX => 'oldClientSecret',
                Settings::WEBHOOK_ID => 'someWebhookId',
            ],
            TestDefaults::SALES_CHANNEL => [
                Settings::CLIENT_ID => 'oldSpecificClientId',
                Settings::CLIENT_SECRET => 'oldSpecificClientSecret',
                Settings::SANDBOX => true,
                Settings::CLIENT_ID_SANDBOX => 'oldSpecificClientId',
                Settings::CLIENT_SECRET_SANDBOX => 'oldSpecificClientSecret',
                Settings::WEBHOOK_ID => 'someSpecificOwnWebhookId',
            ],
        ];
    }
}

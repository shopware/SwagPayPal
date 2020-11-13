<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Shopware\Core\System\SystemConfig\Service\ConfigurationService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Webhook\WebhookServiceMock;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigController;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\Request;

class WebhookSystemConfigControllerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

    private const OTHER_CLIENT_ID = 'otherClientId';
    private const OTHER_CLIENT_SECRET = 'otherClientSecret';

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var SystemConfigController
     */
    private $undecoratedController;

    /**
     * @var WebhookServiceMock
     */
    private $webhookService;

    protected function setUp(): void
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->getContainer()->get(ConfigurationService::class);
        $this->configurationService = $configurationService;
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->systemConfigService = $systemConfigService;

        // creating new instance without decoration
        $this->undecoratedController = new SystemConfigController($configurationService, $systemConfigService);

        $this->webhookService = new WebhookServiceMock();
    }

    public function testBatchSaveWithChangedSandboxMode(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox'] = false;
        $newConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox'] = false;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertFalse($this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox'));
        static::assertFalse($this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox'), Defaults::SALES_CHANNEL);

        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveWithChangedSandboxCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'] = self::OTHER_CLIENT_ID;
        $newConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'] = self::OTHER_CLIENT_SECRET;
        $newConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'] = self::OTHER_CLIENT_ID;
        $newConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'), Defaults::SALES_CHANNEL);
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'), Defaults::SALES_CHANNEL);

        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveWithChangedRegularCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $oldConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $oldConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $newConfig = $this->getDefaultConfig();
        $newConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'] = self::OTHER_CLIENT_ID;
        $newConfig['null'][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'] = self::OTHER_CLIENT_SECRET;
        $newConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'] = self::OTHER_CLIENT_ID;
        $newConfig[Defaults::SALES_CHANNEL][SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'));
        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'), Defaults::SALES_CHANNEL);
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'), Defaults::SALES_CHANNEL);

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testBatchSaveNoChanges(): void
    {
        $oldConfig = $this->getDefaultConfig();
        $newConfig = $this->getDefaultConfig();

        $this->undecoratedController->batchSaveConfiguration($this->createBatchRequest($oldConfig));

        $this->createWebhookSystemConfigController()->batchSaveConfiguration($this->createBatchRequest($newConfig));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL, 'null'], $this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedSandboxMode(): void
    {
        $oldConfig = $this->getDefaultConfig()[Defaults::SALES_CHANNEL];
        $newConfig = $this->getDefaultConfig()[Defaults::SALES_CHANNEL];
        $newConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox'] = false;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, Defaults::SALES_CHANNEL));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, Defaults::SALES_CHANNEL));

        static::assertFalse($this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox', Defaults::SALES_CHANNEL));

        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL], $this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL], $this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedSandboxCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig()['null'];
        $oldConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $newConfig = $this->getDefaultConfig()['null'];
        $newConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'] = self::OTHER_CLIENT_ID;
        $newConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, null));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, null));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSecret'));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing(['null'], $this->webhookService->getRegistrations());
    }

    public function testSaveWithChangedRegularCredentials(): void
    {
        $oldConfig = $this->getDefaultConfig()[Defaults::SALES_CHANNEL];
        $oldConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY] = null;
        $newConfig = $this->getDefaultConfig()[Defaults::SALES_CHANNEL];
        $newConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'] = self::OTHER_CLIENT_ID;
        $newConfig[SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'] = self::OTHER_CLIENT_SECRET;

        $this->undecoratedController->saveConfiguration($this->createSingleRequest($oldConfig, Defaults::SALES_CHANNEL));
        $this->createWebhookSystemConfigController()->saveConfiguration($this->createSingleRequest($newConfig, Defaults::SALES_CHANNEL));

        static::assertSame(self::OTHER_CLIENT_ID, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId', Defaults::SALES_CHANNEL));
        static::assertSame(self::OTHER_CLIENT_SECRET, $this->systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret', Defaults::SALES_CHANNEL));

        static::assertEmpty($this->webhookService->getDeregistrations());
        static::assertEqualsCanonicalizing([Defaults::SALES_CHANNEL], $this->webhookService->getRegistrations());
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

    private function createWebhookSystemConfigController(): WebhookSystemConfigController
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);

        return new WebhookSystemConfigController(
            $this->configurationService,
            $this->systemConfigService,
            $settingsService,
            new WebhookSystemConfigHelper(
                new NullLogger(),
                $settingsService,
                $this->webhookService
            )
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
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => 'oldClientId',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => 'oldClientSecret',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox' => 'oldClientId',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox' => 'oldClientSecret',
                SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY => 'someWebhookId',
            ],
            Defaults::SALES_CHANNEL => [
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => 'oldClientId',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => 'oldClientSecret',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox' => 'oldClientId',
                SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox' => 'oldClientSecret',
                SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_ID_KEY => 'someWebhookId',
            ],
        ];
    }
}

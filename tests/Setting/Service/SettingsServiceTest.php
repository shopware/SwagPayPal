<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Setting\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\Util\ConfigReader;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Webhook\WebhookService;

class SettingsServiceTest extends TestCase
{
    use KernelTestBehaviour;

    private const PREFIX = 'SwagPayPal.settings.';

    public function testEmptyGetSettings(): void
    {
        $settingsProvider = new SettingsService($this->createSystemConfigServiceMock());
        $this->expectException(PayPalSettingsInvalidException::class);
        $settingsProvider->getSettings();
    }

    public function getProvider(): array
    {
        $prefix = static::PREFIX;

        return [
            [$prefix . 'clientId', 'getClientId', 'testClientId'],
            [$prefix . 'clientSecret', 'getClientSecret', 'getTestClientId'],
            [$prefix . 'sandbox', 'getSandbox', true],
            [$prefix . 'intent', 'getIntent', PaymentIntent::SALE],
            [$prefix . 'submitCart', 'getSubmitCart', false],
            [$prefix . 'webhookId', 'getWebhookId', 'testWebhookId'],
            [$prefix . WebhookService::WEBHOOK_TOKEN_CONFIG_KEY, 'getwebhookExecuteToken', 'testWebhookToken'],
            [$prefix . 'brandName', 'getBrandName', 'Awesome brand'],
            [$prefix . 'landingPage', 'getLandingPage', ApplicationContext::LANDING_PAGE_TYPE_LOGIN],
            [$prefix . 'sendOrderNumber', 'getSendOrderNumber', false],
            [$prefix . 'orderNumberPrefix', 'getOrderNumberPrefix', 'TEST_'],
            [$prefix . 'spbCheckoutEnabled', 'getSpbCheckoutEnabled', true],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet(string $key, string $getterName, $value): void
    {
        $settingValues = $this->getRequiredConfigValues();
        $settingValues[$key] = $value;

        $settingsService = new SettingsService($this->createSystemConfigServiceMock($settingValues));
        $settings = $settingsService->getSettings();

        static::assertTrue(
            method_exists($settings, $getterName),
            'getter ' . $getterName . ' does not exist'
        );
        static::assertSame($value, $settings->$getterName());
    }

    public function updateProvider(): array
    {
        return [
            ['clientId', 'getClientId', 'testClientId'],
            ['clientSecret', 'getClientSecret', 'getTestClientId'],
            ['sandbox', 'getSandbox', true],
            ['intent', 'getIntent', PaymentIntent::SALE],
            ['submitCart', 'getSubmitCart', false],
            ['webhookId', 'getWebhookId', 'testWebhookId'],
            [WebhookService::WEBHOOK_TOKEN_CONFIG_KEY, 'getwebhookExecuteToken', 'testWebhookToken'],
            ['brandName', 'getBrandName', 'Awesome brand'],
            ['landingPage', 'getLandingPage', ApplicationContext::LANDING_PAGE_TYPE_LOGIN],
            ['sendOrderNumber', 'getSendOrderNumber', false],
            ['orderNumberPrefix', 'getOrderNumberPrefix', 'TEST_'],
            ['spbCheckoutEnabled', 'getSpbCheckoutEnabled', true],
        ];
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate(string $key, string $getterName, $value): void
    {
        $settingsService = new SettingsService($this->createSystemConfigServiceMock($this->getRequiredConfigValues()));

        $settingsService->updateSettings([$key => $value]);
        $settings = $settingsService->getSettings();

        static::assertTrue(
            method_exists($settings, $getterName),
            'getter ' . $getterName . ' does not exist'
        );
        static::assertSame($value, $settings->$getterName());
    }

    public function testGetWithWrongPrefix(): void
    {
        $values = ['wrongDomain.brandName' => 'Wrong brand'];
        $settingsService = new SettingsService($this->createSystemConfigServiceMock($values));
        $this->expectException(PayPalSettingsInvalidException::class);
        $settingsService->getSettings();
    }

    private function createSystemConfigServiceMock(array $settings = []): SystemConfigServiceMock
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $systemConfigRepo = $definitionRegistry->getRepository(
            (new SystemConfigDefinition())->getEntityName()
        );

        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);
        $systemConfigService = new SystemConfigServiceMock($connection, $systemConfigRepo, new ConfigReader());
        foreach ($settings as $key => $value) {
            $systemConfigService->set($key, $value);
        }

        return $systemConfigService;
    }

    private function getRequiredConfigValues(): array
    {
        return [
            self::PREFIX . 'clientId' => 'testclientid',
            self::PREFIX . 'clientSecret' => 'testclientid',
        ];
    }
}

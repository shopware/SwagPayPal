<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Webhook\WebhookService;

class SettingsServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

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
            [$prefix . 'clientIdSandbox', 'getClientIdSandbox', 'testClientIdSandbox'],
            [$prefix . 'clientSecretSandbox', 'getClientSecretSandbox', 'getTestClientIdSandbox'],
            [$prefix . 'sandbox', 'getSandbox', true],
            [$prefix . 'intent', 'getIntent', PaymentIntent::SALE],
            [$prefix . 'submitCart', 'getSubmitCart', false],
            [$prefix . 'webhookId', 'getWebhookId', GuzzleClientMock::TEST_WEBHOOK_ID],
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
     *
     * @param string|bool $value
     */
    public function testGet(string $key, string $getterName, $value): void
    {
        $settingValues = $this->getRequiredConfigValues();
        $settingValues[$key] = $value;

        $settingsService = new SettingsService($this->createSystemConfigServiceMock($settingValues));
        $settings = $settingsService->getSettings();

        static::assertTrue(\method_exists($settings, $getterName), 'getter ' . $getterName . ' does not exist');
        static::assertSame($value, $settings->$getterName());
    }

    public function updateProvider(): array
    {
        return [
            ['clientId', 'getClientId', 'testClientId'],
            ['clientSecret', 'getClientSecret', 'getTestClientId'],
            ['clientIdSandbox', 'getClientIdSandbox', 'testClientIdSandbox'],
            ['clientSecretSandbox', 'getClientSecretSandbox', 'getTestClientIdSandbox'],
            ['sandbox', 'getSandbox', true],
            ['intent', 'getIntent', PaymentIntent::SALE],
            ['submitCart', 'getSubmitCart', false],
            ['webhookId', 'getWebhookId', GuzzleClientMock::TEST_WEBHOOK_ID],
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
     *
     * @param string|bool $value
     */
    public function testUpdate(string $key, string $getterName, $value): void
    {
        $settingsService = new SettingsService($this->createSystemConfigServiceMock($this->getRequiredConfigValues()));

        $settingsService->updateSettings([$key => $value]);
        $settings = $settingsService->getSettings();

        static::assertTrue(\method_exists($settings, $getterName), 'getter ' . $getterName . ' does not exist');
        static::assertSame($value, $settings->$getterName());
    }

    public function testGetWithWrongPrefix(): void
    {
        $values = ['wrongDomain.brandName' => 'Wrong brand'];
        $settingsService = new SettingsService($this->createSystemConfigServiceMock($values));
        $this->expectException(PayPalSettingsInvalidException::class);
        $settingsService->getSettings();
    }

    private function getRequiredConfigValues(): array
    {
        return [
            self::PREFIX . 'clientId' => 'testclientid',
            self::PREFIX . 'clientSecret' => 'testclientid',
            self::PREFIX . 'clientIdSandbox' => 'testclientid',
            self::PREFIX . 'clientSecretSandbox' => 'testclientid',
        ];
    }
}

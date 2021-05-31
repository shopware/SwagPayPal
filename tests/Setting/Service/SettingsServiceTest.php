<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;

class SettingsServiceTest extends TestCase
{
    use ServicesTrait;

    public function testEmptyGetSettings(): void
    {
        $settingsProvider = new SettingsService($this->createSystemConfigServiceMock(), new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $settingsProvider->getSettings();
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string|bool $value
     */
    public function testGet(string $key, string $getterName, $value): void
    {
        $settingValues = $this->getRequiredConfigValues();
        $settingValues[$key] = $value;

        $settingsService = new SettingsService($this->createSystemConfigServiceMock($settingValues), new NullLogger());
        $settings = $settingsService->getSettings();

        static::assertTrue(\method_exists($settings, $getterName), 'getter ' . $getterName . ' does not exist');
        static::assertSame($value, $settings->$getterName());
    }

    public function dataProvider(): array
    {
        return [
            [Settings::CLIENT_ID, 'getClientId', 'testClientId'],
            [Settings::CLIENT_SECRET, 'getClientSecret', 'testClientSecret'],
            [Settings::CLIENT_ID_SANDBOX, 'getClientIdSandbox', 'testClientIdSandbox'],
            [Settings::CLIENT_SECRET_SANDBOX, 'getClientSecretSandbox', 'getTestClientIdSandbox'],
            [Settings::SANDBOX, 'getSandbox', true],
            [Settings::INTENT, 'getIntent', PaymentIntentV1::SALE],
            [Settings::SUBMIT_CART, 'getSubmitCart', false],
            [Settings::WEBHOOK_ID, 'getWebhookId', GuzzleClientMock::TEST_WEBHOOK_ID],
            [Settings::WEBHOOK_EXECUTE_TOKEN, 'getWebhookExecuteToken', 'testWebhookToken'],
            [Settings::BRAND_NAME, 'getBrandName', 'Awesome brand'],
            [Settings::LANDING_PAGE, 'getLandingPage', ApplicationContext::LANDING_PAGE_TYPE_LOGIN],
            [Settings::SEND_ORDER_NUMBER, 'getSendOrderNumber', false],
            [Settings::ORDER_NUMBER_PREFIX, 'getOrderNumberPrefix', 'TEST_'],
            [Settings::SPB_CHECKOUT_ENABLED, 'getSpbCheckoutEnabled', true],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string|bool $value
     */
    public function testUpdate(string $key, string $getterName, $value): void
    {
        $settingsService = new SettingsService(
            $this->createSystemConfigServiceMock($this->getRequiredConfigValues()),
            new NullLogger()
        );

        $key = \str_replace(Settings::SYSTEM_CONFIG_DOMAIN, '', $key);
        $settingsService->updateSettings([$key => $value]);
        $settings = $settingsService->getSettings();

        static::assertTrue(\method_exists($settings, $getterName), 'getter ' . $getterName . ' does not exist');
        static::assertSame($value, $settings->$getterName());
    }

    public function testGetWithWrongPrefix(): void
    {
        $values = ['wrongDomain.brandName' => 'Wrong brand'];
        $settingsService = new SettingsService($this->createSystemConfigServiceMock($values), new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $settingsService->getSettings();
    }

    private function getRequiredConfigValues(): array
    {
        return [
            Settings::CLIENT_ID => 'testclientid',
            Settings::CLIENT_SECRET => 'testclientid',
            Settings::CLIENT_ID_SANDBOX => 'testclientid',
            Settings::CLIENT_SECRET_SANDBOX => 'testclientid',
        ];
    }
}

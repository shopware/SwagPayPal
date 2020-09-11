<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\RestApi\V1\PaymentIntentV1;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Webhook\WebhookService;

class SwagPayPalSettingStructTest extends TestCase
{
    private const ORDER_NUMBER_PREFIX = 'SW';
    private const BUTTON_COLOR = 'blue';
    private const BUTTON_SHAPE = 'rect';
    private const BUTTON_LANGUAGE_ISO = 'en_GB';
    private const CLIENT_ID = 'SomeClientId';
    private const CLIENT_SECRET = 'SomeClientSecret';
    private const CLIENT_ID_SANDBOX = 'SomeClientIdForSandboxing';
    private const CLIENT_SECRET_SANDBOX = 'SomeClientSecretForSandboxing';
    private const WEBHOOK_ID = 'SomeWebhookId';
    private const WEBHOOK_EXECUTE_TOKEN = 'SomeWebhookExecuteToken';
    private const BRAND_NAME = 'SomeBrandName';

    public function testStructWithAssign(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->assign([
            'clientId' => self::CLIENT_ID,
            'clientSecret' => self::CLIENT_SECRET,
            'clientIdSandbox' => self::CLIENT_ID_SANDBOX,
            'clientSecretSandbox' => self::CLIENT_SECRET_SANDBOX,
            'sandbox' => false,
            'intent' => PaymentIntentV1::SALE,
            'submitCart' => false,
            'webhookId' => self::WEBHOOK_ID,
            WebhookService::WEBHOOK_TOKEN_CONFIG_KEY => self::WEBHOOK_EXECUTE_TOKEN,
            'brandName' => self::BRAND_NAME,
            'landingPage' => ApplicationContext::LANDING_PAGE_TYPE_LOGIN,
            'sendOrderNumber' => false,
            'orderNumberPrefix' => self::ORDER_NUMBER_PREFIX,
            'ecsDetailEnabled' => false,
            'ecsCartEnabled' => false,
            'ecsOffCanvasEnabled' => false,
            'ecsLoginEnabled' => false,
            'ecsListingEnabled' => false,
            'ecsButtonColor' => self::BUTTON_COLOR,
            'ecsButtonShape' => self::BUTTON_SHAPE,
            'ecsSubmitCart' => false,
            'ecsButtonLanguageIso' => self::BUTTON_LANGUAGE_ISO,
            'spbCheckoutEnabled' => false,
            'spbAlternativePaymentMethodsEnabled' => false,
        ]);

        $this->assertStruct($settings);
    }

    public function testStructWithSetters(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId(self::CLIENT_ID);
        $settings->setClientSecret(self::CLIENT_SECRET);
        $settings->setClientIdSandbox(self::CLIENT_ID_SANDBOX);
        $settings->setClientSecretSandbox(self::CLIENT_SECRET_SANDBOX);
        $settings->setSandbox(false);
        $settings->setIntent(PaymentIntentV1::SALE);
        $settings->setSubmitCart(false);
        $settings->setWebhookId(self::WEBHOOK_ID);
        $settings->setWebhookExecuteToken(self::WEBHOOK_EXECUTE_TOKEN);
        $settings->setBrandName(self::BRAND_NAME);
        $settings->setLandingPage(ApplicationContext::LANDING_PAGE_TYPE_LOGIN);
        $settings->setSendOrderNumber(false);
        $settings->setOrderNumberPrefix(self::ORDER_NUMBER_PREFIX);
        $settings->setEcsDetailEnabled(false);
        $settings->setEcsCartEnabled(false);
        $settings->setEcsOffCanvasEnabled(false);
        $settings->setEcsLoginEnabled(false);
        $settings->setEcsListingEnabled(false);
        $settings->setEcsButtonColor(self::BUTTON_COLOR);
        $settings->setEcsButtonShape(self::BUTTON_SHAPE);
        $settings->setEcsSubmitCart(false);
        $settings->setEcsButtonLanguageIso(self::BUTTON_LANGUAGE_ISO);
        $settings->setSpbCheckoutEnabled(false);
        $settings->setSpbAlternativePaymentMethodsEnabled(false);

        $this->assertStruct($settings);
    }

    private function assertStruct(SwagPayPalSettingStruct $settings): void
    {
        static::assertSame(self::CLIENT_ID, $settings->getClientId());
        static::assertSame(self::CLIENT_SECRET, $settings->getClientSecret());
        static::assertSame(self::CLIENT_ID_SANDBOX, $settings->getClientIdSandbox());
        static::assertSame(self::CLIENT_SECRET_SANDBOX, $settings->getClientSecretSandbox());
        static::assertFalse($settings->getSandbox());
        static::assertSame(PaymentIntentV1::SALE, $settings->getIntent());
        static::assertFalse($settings->getSubmitCart());
        static::assertSame(self::WEBHOOK_ID, $settings->getWebhookId());
        static::assertSame(self::WEBHOOK_EXECUTE_TOKEN, $settings->getWebhookExecuteToken());
        static::assertSame(self::BRAND_NAME, $settings->getBrandName());
        static::assertSame(ApplicationContext::LANDING_PAGE_TYPE_LOGIN, $settings->getLandingPage());
        static::assertFalse($settings->getSendOrderNumber());
        static::assertSame(self::ORDER_NUMBER_PREFIX, $settings->getOrderNumberPrefix());
        static::assertFalse($settings->getEcsDetailEnabled());
        static::assertFalse($settings->getEcsCartEnabled());
        static::assertFalse($settings->getEcsOffCanvasEnabled());
        static::assertFalse($settings->getEcsLoginEnabled());
        static::assertFalse($settings->getEcsListingEnabled());
        static::assertSame(self::BUTTON_COLOR, $settings->getEcsButtonColor());
        static::assertSame(self::BUTTON_SHAPE, $settings->getEcsButtonShape());
        static::assertFalse($settings->getEcsSubmitCart());
        static::assertSame(self::BUTTON_LANGUAGE_ISO, $settings->getEcsButtonLanguageIso());
        static::assertFalse($settings->getSpbCheckoutEnabled());
        static::assertFalse($settings->getSpbAlternativePaymentMethodsEnabled());
    }
}

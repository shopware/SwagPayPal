<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

class SwagPayPalSettingStructTest extends TestCase
{
    private const ORDER_NUMBER_PREFIX = 'SW';
    private const BUTTON_COLOR = 'blue';
    private const BUTTON_SHAPE = 'rect';
    private const BUTTON_LANGUAGE_ISO = 'en_GB';
    private const CLIENT_ID = 'SomeClientId';
    private const CLIENT_SECRET = 'SomeClientSecret';
    private const WEBHOOK_ID = 'SomeWebhookId';
    private const WEBHOOK_EXECUTE_TOKEN = 'SomeWebhookExecuteToken';
    private const BRAND_NAME = 'SomeBrandName';

    public function testStructWithAssign(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->assign([
            'clientId' => static::CLIENT_ID,
            'clientSecret' => static::CLIENT_SECRET,
            'sandbox' => false,
            'intent' => PaymentIntent::SALE,
            'submitCart' => false,
            'webhookId' => static::WEBHOOK_ID,
            'webhookExecuteToken' => static::WEBHOOK_EXECUTE_TOKEN,
            'brandName' => static::BRAND_NAME,
            'landingPage' => ApplicationContext::LANDING_PAGE_TYPE_LOGIN,
            'sendOrderNumber' => false,
            'orderNumberPrefix' => static::ORDER_NUMBER_PREFIX,
            'ecsDetailEnabled' => false,
            'ecsCartEnabled' => false,
            'ecsOffCanvasEnabled' => false,
            'ecsLoginEnabled' => false,
            'ecsListingEnabled' => false,
            'ecsButtonColor' => static::BUTTON_COLOR,
            'ecsButtonShape' => static::BUTTON_SHAPE,
            'ecsSubmitCart' => false,
            'ecsButtonLanguageIso' => static::BUTTON_LANGUAGE_ISO,
            'spbCheckoutEnabled' => false,
            'spbAlternativePaymentMethodsEnabled' => false,
        ]);

        $this->assertStruct($settings);
    }

    public function testStructWithSetters(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId(static::CLIENT_ID);
        $settings->setClientSecret(static::CLIENT_SECRET);
        $settings->setSandbox(false);
        $settings->setIntent(PaymentIntent::SALE);
        $settings->setSubmitCart(false);
        $settings->setWebhookId(static::WEBHOOK_ID);
        $settings->setWebhookExecuteToken(static::WEBHOOK_EXECUTE_TOKEN);
        $settings->setBrandName(static::BRAND_NAME);
        $settings->setLandingPage(ApplicationContext::LANDING_PAGE_TYPE_LOGIN);
        $settings->setSendOrderNumber(false);
        $settings->setOrderNumberPrefix(static::ORDER_NUMBER_PREFIX);
        $settings->setEcsDetailEnabled(false);
        $settings->setEcsCartEnabled(false);
        $settings->setEcsOffCanvasEnabled(false);
        $settings->setEcsLoginEnabled(false);
        $settings->setEcsListingEnabled(false);
        $settings->setEcsButtonColor(static::BUTTON_COLOR);
        $settings->setEcsButtonShape(static::BUTTON_SHAPE);
        $settings->setEcsSubmitCart(false);
        $settings->setEcsButtonLanguageIso(static::BUTTON_LANGUAGE_ISO);
        $settings->setSpbCheckoutEnabled(false);
        $settings->setSpbAlternativePaymentMethodsEnabled(false);

        $this->assertStruct($settings);
    }

    private function assertStruct(SwagPayPalSettingStruct $settings): void
    {
        static::assertSame(static::CLIENT_ID, $settings->getClientId());
        static::assertSame(static::CLIENT_SECRET, $settings->getClientSecret());
        static::assertFalse($settings->getSandbox());
        static::assertSame(PaymentIntent::SALE, $settings->getIntent());
        static::assertFalse($settings->getSubmitCart());
        static::assertSame(static::WEBHOOK_ID, $settings->getWebhookId());
        static::assertSame(static::WEBHOOK_EXECUTE_TOKEN, $settings->getWebhookExecuteToken());
        static::assertSame(static::BRAND_NAME, $settings->getBrandName());
        static::assertSame(ApplicationContext::LANDING_PAGE_TYPE_LOGIN, $settings->getLandingPage());
        static::assertFalse($settings->getSendOrderNumber());
        static::assertSame(static::ORDER_NUMBER_PREFIX, $settings->getOrderNumberPrefix());
        static::assertFalse($settings->getEcsDetailEnabled());
        static::assertFalse($settings->getEcsCartEnabled());
        static::assertFalse($settings->getEcsOffCanvasEnabled());
        static::assertFalse($settings->getEcsLoginEnabled());
        static::assertFalse($settings->getEcsListingEnabled());
        static::assertSame(static::BUTTON_COLOR, $settings->getEcsButtonColor());
        static::assertSame(static::BUTTON_SHAPE, $settings->getEcsButtonShape());
        static::assertFalse($settings->getEcsSubmitCart());
        static::assertSame(static::BUTTON_LANGUAGE_ISO, $settings->getEcsButtonLanguageIso());
        static::assertFalse($settings->getSpbCheckoutEnabled());
        static::assertFalse($settings->getSpbAlternativePaymentMethodsEnabled());
    }
}

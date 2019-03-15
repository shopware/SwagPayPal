<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Setting\Service;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Payment\ApplicationContext;
use SwagPayPal\PayPal\PaymentIntent;
use SwagPayPal\Setting\Service\SettingsService;
use SwagPayPal\Setting\SwagPayPalSettingGeneralEntity;

class SettingsServiceMock extends SettingsService
{
    public const PAYPAL_SETTING_ID = 'testSettingsId';
    public const PAYPAL_SETTING_WITHOUT_TOKEN = 'settingsWithoutToken';
    public const PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID = 'settingsWithoutTokenAndId';
    public const PAYPAL_SETTING_WITH_SUBMIT_CART = 'settingWithSubmitCart';
    public const PAYPAL_SETTING_WITH_INVALID_INTENT = 'settingWithInvalidIntent';
    public const PAYPAL_SETTING_WITHOUT_BRAND_NAME = 'settingWithoutBrandName';
    public const PAYPAL_SETTING_WITH_ORDER_NUMBER = 'settingWithOrderNumber';
    public const PAYPAL_SETTING_WITH_ORDER_NUMBER_WITHOUT_PREFIX = 'settingWithOrderNumberWithoutPrefix';
    public const PAYPAL_SETTING_ORDER_NUMBER_PREFIX = 'TEST_';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';
    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    public function getSettings(Context $context): SwagPayPalSettingGeneralEntity
    {
        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN)) {
            $settingsStruct = $this->createBasicSettingStruct();
            $settingsStruct->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);
            $settingsStruct->setLandingPage(ApplicationContext::LANDINGPAGE_TYPE_BILLING);

            return $settingsStruct;
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID)) {
            $settingsStruct = $this->createBasicSettingStruct();
            $settingsStruct->setLandingPage(ApplicationContext::LANDINGPAGE_TYPE_LOGIN);

            return $settingsStruct;
        }

        $settingsStruct = $this->createDefaultSettingStruct();

        if ($context->hasExtension(self::PAYPAL_SETTING_WITH_INVALID_INTENT)) {
            $settingsStruct->setIntent('invalid');
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_BRAND_NAME)) {
            $settingsStruct->setBrandName('');
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITH_SUBMIT_CART)) {
            $settingsStruct->setSubmitCart(true);
            $settingsStruct->setLandingPage('Foo');
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITH_ORDER_NUMBER)) {
            $settingsStruct->setSendOrderNumber(true);
            $settingsStruct->setOrderNumberPrefix(self::PAYPAL_SETTING_ORDER_NUMBER_PREFIX);
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITH_ORDER_NUMBER_WITHOUT_PREFIX)) {
            $settingsStruct->setSendOrderNumber(true);
        }

        return $settingsStruct;
    }

    private function createBasicSettingStruct(): SwagPayPalSettingGeneralEntity
    {
        $settingsStruct = new SwagPayPalSettingGeneralEntity();
        $settingsStruct->setId(self::PAYPAL_SETTING_ID);
        $settingsStruct->setIntent(PaymentIntent::SALE);
        $settingsStruct->setSubmitCart(false);
        $settingsStruct->setSendOrderNumber(false);

        return $settingsStruct;
    }

    private function createDefaultSettingStruct(): SwagPayPalSettingGeneralEntity
    {
        $settingsStruct = $this->createBasicSettingStruct();
        $settingsStruct->setBrandName('Test Brand');
        $settingsStruct->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $settingsStruct->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);
        $settingsStruct->setLandingPage('Login');

        return $settingsStruct;
    }
}

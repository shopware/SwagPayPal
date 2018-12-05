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
use SwagPayPal\Setting\Service\SettingsProviderInterface;
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;

class SettingsProviderMock implements SettingsProviderInterface
{
    public const PAYPAL_SETTING_ID = 'testSettingsId';

    public const PAYPAL_SETTING_WITHOUT_TOKEN = 'settingsWithoutToken';

    public const PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID = 'settingsWithoutTokenAndId';

    public const PAYPAL_SETTING_WITH_SUBMIT_CART = 'settingWithSubmitCart';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';

    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    public function getSettings(Context $context): SwagPayPalSettingGeneralStruct
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

        $settingsStruct = $this->createBasicSettingStruct();
        $settingsStruct->setBrandName('Test Brand');
        $settingsStruct->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $settingsStruct->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);
        $settingsStruct->setLandingPage('Login');

        if ($context->hasExtension(self::PAYPAL_SETTING_WITH_SUBMIT_CART)) {
            $settingsStruct->setSubmitCart(true);
            $settingsStruct->setLandingPage('Quatsch');
        }

        return $settingsStruct;
    }

    private function createBasicSettingStruct(): SwagPayPalSettingGeneralStruct
    {
        $settingsStruct = new SwagPayPalSettingGeneralStruct();
        $settingsStruct->setId(self::PAYPAL_SETTING_ID);
        $settingsStruct->setSubmitCart(false);

        return $settingsStruct;
    }
}

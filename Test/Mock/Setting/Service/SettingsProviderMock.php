<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Setting\Service;

use Shopware\Core\Framework\Context;
use SwagPayPal\Setting\Service\SettingsProviderInterface;
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;

class SettingsProviderMock implements SettingsProviderInterface
{
    public const PAYPAL_SETTING_ID = 'testSettingsId';

    public const PAYPAL_SETTING_WITHOUT_TOKEN = 'settingsWithoutToken';

    public const PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID = 'settingsWithoutTokenAndId';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';

    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    public function getSettings(Context $context): SwagPayPalSettingGeneralStruct
    {
        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN)) {
            $settingsStruct = $this->createBasicSettingStruct();
            $settingsStruct->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);

            return $settingsStruct;
        }

        if ($context->hasExtension(self::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID)) {
            $settingsStruct = $this->createBasicSettingStruct();

            return $settingsStruct;
        }

        $settingsStruct = $this->createBasicSettingStruct();
        $settingsStruct->setBrandName('Test Brand');
        $settingsStruct->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $settingsStruct->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);

        return $settingsStruct;
    }

    private function createBasicSettingStruct(): SwagPayPalSettingGeneralStruct
    {
        $settingsStruct = new SwagPayPalSettingGeneralStruct();
        $settingsStruct->setId(self::PAYPAL_SETTING_ID);

        return $settingsStruct;
    }
}

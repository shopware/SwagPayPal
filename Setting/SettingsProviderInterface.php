<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting;

use Shopware\Core\Framework\Context;
use SwagPayPal\Setting\Exception\PayPalSettingsNotFoundException;

interface SettingsProviderInterface
{
    /**
     * @throws PayPalSettingsNotFoundException
     */
    public function getSettings(Context $context): SwagPayPalSettingGeneralStruct;
}

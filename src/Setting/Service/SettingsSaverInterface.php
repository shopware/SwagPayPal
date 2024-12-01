<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Setting\Struct\SettingsInformationStruct;

#[Package('checkout')]
interface SettingsSaverInterface
{
    public function save(array $settings, ?string $salesChannelId = null): SettingsInformationStruct;
}

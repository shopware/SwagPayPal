<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsService;

class Update
{
    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    public function __construct(SystemConfigService $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    public function update(UpdateContext $updateContext): void
    {
        if (version_compare($updateContext->getCurrentPluginVersion(), '1.1.0', '<')) {
            $this->updateTo110();
        }
    }

    private function updateTo110(): void
    {
        $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'installmentBannerEnabled', true);
    }
}

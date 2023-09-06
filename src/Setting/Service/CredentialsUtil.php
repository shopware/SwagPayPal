<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class CredentialsUtil implements CredentialsUtilInterface
{
    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function isSandbox(?string $salesChannelId = null): bool
    {
        return $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId);
    }

    public function getClientId(?string $salesChannelId = null): string
    {
        if ($this->isSandbox($salesChannelId)) {
            return $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId);
        }

        return $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);
    }

    public function getMerchantPayerId(?string $salesChannelId = null): string
    {
        if ($this->isSandbox($salesChannelId)) {
            return $this->systemConfigService->getString(Settings::MERCHANT_PAYER_ID_SANDBOX, $salesChannelId);
        }

        return $this->systemConfigService->getString(Settings::MERCHANT_PAYER_ID, $salesChannelId);
    }

    public function getBaseUrl(?string $salesChannelId = null): string
    {
        return $this->isSandbox($salesChannelId) ? BaseURL::SANDBOX : BaseURL::LIVE;
    }
}

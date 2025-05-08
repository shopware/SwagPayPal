<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class ApiContextFactory implements ApiContextFactoryInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly SystemConfigService $systemConfigService,
        private readonly CredentialsUtilInterface $credentialsUtil,
    ) {
    }

    public function getApiContext(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
    ): ApiContext {
        $this->settingsValidationService->validate($salesChannelId);

        $sandbox = $this->credentialsUtil->isSandbox($salesChannelId);
        $merchantId = $this->credentialsUtil->getMerchantPayerId($salesChannelId);
        $clientId = $this->credentialsUtil->getClientId($salesChannelId);
        $clientSecret = $this->systemConfigService->getString(
            $sandbox ? Settings::CLIENT_SECRET_SANDBOX : Settings::CLIENT_SECRET,
            $salesChannelId
        );

        return (new ApiContext(
            new CredentialsOAuthContext($clientId, $clientSecret),
            $sandbox,
        ))->withPartnerAttributionId($merchantId ? PartnerAttributionId::PAYPAL_PPCP : $partnerAttributionId);
    }
}

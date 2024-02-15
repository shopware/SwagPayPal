<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class CredentialProvider implements CredentialProviderInterface
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

    public function createCredentialsObject(?string $salesChannelId): OAuthCredentials
    {
        $this->settingsValidationService->validate($salesChannelId);

        $isSandbox = $this->credentialsUtil->isSandbox($salesChannelId);

        $clientId = $this->credentialsUtil->getClientId($salesChannelId);
        $clientSecret = $this->systemConfigService->getString(
            $isSandbox ? Settings::CLIENT_SECRET_SANDBOX : Settings::CLIENT_SECRET,
            $salesChannelId
        );

        $url = $this->credentialsUtil->getBaseUrl($salesChannelId);

        return OAuthCredentials::createFromRestCredentials($clientId, $clientSecret, $url);
    }

    public function createAuthorizationHeaders(Token $token, ?string $merchantPayerId): array
    {
        return [
            'Authorization' => \sprintf('%s %s', $token->getTokenType(), $token->getAccessToken()),
        ];
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\OauthIntegration;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_merchant_integrations_oauth_integration_oauth_third_party')]
#[Package('checkout')]
class OauthThirdParty extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $accessToken;

    #[OA\Property(type: 'string')]
    protected string $merchantClientId;

    #[OA\Property(type: 'string')]
    protected string $partnerClientId;

    #[OA\Property(type: 'string')]
    protected string $refreshToken;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $scopes;

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getMerchantClientId(): string
    {
        return $this->merchantClientId;
    }

    public function setMerchantClientId(string $merchantClientId): void
    {
        $this->merchantClientId = $merchantClientId;
    }

    public function getPartnerClientId(): string
    {
        return $this->partnerClientId;
    }

    public function setPartnerClientId(string $partnerClientId): void
    {
        $this->partnerClientId = $partnerClientId;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string[] $scopes
     */
    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }
}

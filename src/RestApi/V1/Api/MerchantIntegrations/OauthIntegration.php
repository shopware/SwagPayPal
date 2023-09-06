<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\OauthIntegration\OauthThirdParty;

/**
 * @OA\Schema(schema="swag_paypal_v1_merchant_integrations_oauth_integration")
 */
#[Package('checkout')]
class OauthIntegration extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $integrationMethod;

    /**
     * @OA\Property(type="string")
     */
    protected string $integrationType;

    /**
     * @OA\Property(type="string")
     */
    protected string $status;

    /**
     * @var OauthThirdParty[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_merchant_integrations_oauth_integration_third_party"})
     */
    protected array $oauthThirdParty;

    public function getIntegrationMethod(): string
    {
        return $this->integrationMethod;
    }

    public function setIntegrationMethod(string $integrationMethod): void
    {
        $this->integrationMethod = $integrationMethod;
    }

    public function getIntegrationType(): string
    {
        return $this->integrationType;
    }

    public function setIntegrationType(string $integrationType): void
    {
        $this->integrationType = $integrationType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getOauthThirdParty(): array
    {
        return $this->oauthThirdParty;
    }

    public function setOauthThirdParty(array $oauthThirdParty): void
    {
        $this->oauthThirdParty = $oauthThirdParty;
    }
}

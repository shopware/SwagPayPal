<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference\RestApiIntegration\ThirdPartyDetails;

#[OA\Schema(schema: 'swag_paypal_v2_referral_operation_integration_preference_integration')]
#[Package('checkout')]
class RestApiIntegration extends PayPalApiStruct
{
    public const INTEGRATION_METHOD_TYPE_PAYPAL = 'PAYPAL';
    public const INTEGRATION_TYPE_THIRD_PARTY = 'THIRD_PARTY';

    #[OA\Property(type: 'string', default: self::INTEGRATION_METHOD_TYPE_PAYPAL)]
    protected string $integrationMethod = self::INTEGRATION_METHOD_TYPE_PAYPAL;

    #[OA\Property(type: 'string', default: self::INTEGRATION_TYPE_THIRD_PARTY)]
    protected string $integrationType = self::INTEGRATION_TYPE_THIRD_PARTY;

    #[OA\Property(ref: ThirdPartyDetails::class)]
    protected ThirdPartyDetails $thirdPartyDetails;

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

    public function getThirdPartyDetails(): ThirdPartyDetails
    {
        return $this->thirdPartyDetails;
    }

    public function setThirdPartyDetails(ThirdPartyDetails $thirdPartyDetails): void
    {
        $this->thirdPartyDetails = $thirdPartyDetails;
    }
}

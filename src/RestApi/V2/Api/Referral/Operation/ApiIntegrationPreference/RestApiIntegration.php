<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference\RestApiIntegration\ThirdPartyDetails;

class RestApiIntegration extends PayPalApiStruct
{
    public const INTEGRATION_METHOD_TYPE_PAYPAL = 'PAYPAL';
    public const INTEGRATION_TYPE_THIRD_PARTY = 'THIRD_PARTY';

    /**
     * @var string
     */
    protected $integrationMethod = self::INTEGRATION_METHOD_TYPE_PAYPAL;

    /**
     * @var string
     */
    protected $integrationType = self::INTEGRATION_TYPE_THIRD_PARTY;

    /**
     * @var ThirdPartyDetails
     */
    protected $thirdPartyDetails;

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

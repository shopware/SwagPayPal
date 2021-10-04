<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral\Operation;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference\RestApiIntegration;

/**
 * @OA\Schema(schema="swag_paypal_v2_referral_api_integration_preference")
 */
class ApiIntegrationPreference extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_referral_rest_api_integration")
     */
    protected RestApiIntegration $restApiIntegration;

    public function getRestApiIntegration(): RestApiIntegration
    {
        return $this->restApiIntegration;
    }

    public function setRestApiIntegration(RestApiIntegration $restApiIntegration): void
    {
        $this->restApiIntegration = $restApiIntegration;
    }
}

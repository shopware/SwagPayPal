<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral\Operation;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference\RestApiIntegration;

#[OA\Schema(schema: 'swag_paypal_v2_referral_operation_integration_preference')]
#[Package('checkout')]
class ApiIntegrationPreference extends PayPalApiStruct
{
    #[OA\Property(ref: RestApiIntegration::class)]
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

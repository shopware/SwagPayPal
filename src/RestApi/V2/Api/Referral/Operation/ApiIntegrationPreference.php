<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral\Operation;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference\RestApiIntegration;

class ApiIntegrationPreference extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var RestApiIntegration
     */
    protected $restApiIntegration;

    public function getRestApiIntegration(): RestApiIntegration
    {
        return $this->restApiIntegration;
    }

    public function setRestApiIntegration(RestApiIntegration $restApiIntegration): void
    {
        $this->restApiIntegration = $restApiIntegration;
    }
}

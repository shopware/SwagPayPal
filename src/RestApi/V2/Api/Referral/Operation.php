<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\Operation\ApiIntegrationPreference;

class Operation extends PayPalApiStruct
{
    public const OPERATION_TYPE_API_INTEGRATION = 'API_INTEGRATION';

    /**
     * @var string
     */
    protected $operation = self::OPERATION_TYPE_API_INTEGRATION;

    /**
     * @var ApiIntegrationPreference
     */
    protected $apiIntegrationPreference;

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    public function getApiIntegrationPreference(): ApiIntegrationPreference
    {
        return $this->apiIntegrationPreference;
    }

    public function setApiIntegrationPreference(ApiIntegrationPreference $apiIntegrationPreference): void
    {
        $this->apiIntegrationPreference = $apiIntegrationPreference;
    }
}

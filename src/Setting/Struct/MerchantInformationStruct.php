<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Struct;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;

#[OA\Schema(schema: 'swag_paypal_setting_merchant_information')]
#[Package('checkout')]
class MerchantInformationStruct extends Struct
{
    #[OA\Property(ref: MerchantIntegrations::class)]
    protected ?MerchantIntegrations $merchantIntegrations;

    /**
     * @var array<string, string> key: paymentMethodId, value: capability (see AbstractMethodData)
     */
    #[OA\Property(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]
    protected array $capabilities;

    public function getMerchantIntegrations(): ?MerchantIntegrations
    {
        return $this->merchantIntegrations;
    }

    public function setMerchantIntegrations(?MerchantIntegrations $merchantIntegrations): void
    {
        $this->merchantIntegrations = $merchantIntegrations;
    }

    /**
     * @return array<string, string>
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @param array<string, string> $capabilities
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }
}

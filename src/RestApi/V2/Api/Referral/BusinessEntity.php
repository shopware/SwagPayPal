<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\BusinessEntity\Address;

/**
 * @OA\Schema(schema="swag_paypal_v2_referral_business_entity")
 */
#[Package('checkout')]
class BusinessEntity extends PayPalApiStruct
{
    /**
     * @var Address[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_referral_address"})
     */
    protected array $addresses = [];

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @param Address[] $addresses
     */
    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
    }
}

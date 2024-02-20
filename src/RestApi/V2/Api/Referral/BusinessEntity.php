<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Referral\BusinessEntity\Address;
use Swag\PayPal\RestApi\V2\Api\Referral\BusinessEntity\AddressCollection;

#[OA\Schema(schema: 'swag_paypal_v2_referral_business_entity')]
#[Package('checkout')]
class BusinessEntity extends PayPalApiStruct
{
    #[OA\Property(type: 'array', items: new OA\Items(ref: Address::class))]
    protected AddressCollection $addresses;

    public function getAddresses(): AddressCollection
    {
        return $this->addresses;
    }

    public function setAddresses(AddressCollection $addresses): void
    {
        $this->addresses = $addresses;
    }
}

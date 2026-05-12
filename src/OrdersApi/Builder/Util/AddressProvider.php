<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Common\Address;

#[Package('checkout')]
class AddressProvider
{
    public function createAddress(OrderAddressEntity|CustomerAddressEntity $customerAddress, Address $address): Address
    {
        $address->setAddressLine1($customerAddress->getStreet());

        $additionalAddressLine1 = $customerAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $address->setAddressLine2($additionalAddressLine1);
        }

        $state = $customerAddress->getCountryState();
        if ($state !== null) {
            $address->setAdminArea1($state->getShortCode());
        }

        $address->setAdminArea2($customerAddress->getCity());
        $address->setPostalCode($customerAddress->getZipcode() ?: '');

        $country = $customerAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $address->setCountryCode($countryIso);
            }
        }

        return $address;
    }
}

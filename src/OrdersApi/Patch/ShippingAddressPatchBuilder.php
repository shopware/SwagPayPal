<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Address;
use Swag\PayPal\RestApi\V2\Api\Patch;

class ShippingAddressPatchBuilder
{
    /**
     * @throws AddressNotFoundException
     */
    public function createShippingAddressPatch(CustomerEntity $customer): Patch
    {
        $customerShippingAddress = $customer->getActiveShippingAddress();
        if ($customerShippingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultShippingAddressId());
        }

        $shippingAddress = new Address();

        $shippingAddress->setAddressLine1($customerShippingAddress->getStreet());

        $additionalAddressLine1 = $customerShippingAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $shippingAddress->setAddressLine2($additionalAddressLine1);
        }

        $shippingAddress->setAdminArea2($customerShippingAddress->getCity());

        $country = $customerShippingAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $shippingAddress->setCountryCode($countryIso);
            }
        }

        $shippingAddress->setPostalCode($customerShippingAddress->getZipcode());

        $state = $customerShippingAddress->getCountryState();
        if ($state !== null) {
            $shippingAddress->setAdminArea1($state->getShortCode());
        }

        $shippingAddressArray = \json_decode((string) \json_encode($shippingAddress), true);

        $shippingAddressPatch = new Patch();
        $shippingAddressPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => "/purchase_units/@reference_id=='default'/shipping/address",
        ]);
        $shippingAddressPatch->setValue($shippingAddressArray);

        return $shippingAddressPatch;
    }
}

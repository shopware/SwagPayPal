<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Patch;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\ShippingAddress;

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

        $shippingAddress = new ShippingAddress();
        $shippingAddress->setLine1($customerShippingAddress->getStreet());
        if ($customerShippingAddress->getAdditionalAddressLine1() !== null) {
            $shippingAddress->setLine2($customerShippingAddress->getAdditionalAddressLine1());
        }
        $shippingAddress->setCity($customerShippingAddress->getCity());
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
            $shippingAddress->setState($state->getShortCode());
        }
        if ($customerShippingAddress->getPhoneNumber() !== null) {
            $shippingAddress->setPhone($customerShippingAddress->getPhoneNumber());
        }
        $shippingAddress->setRecipientName($customerShippingAddress->getFirstName() . ' ' . $customerShippingAddress->getLastName());
        $shippingAddressArray = \json_decode((string) \json_encode($shippingAddress), true);

        $shippingAddressPatch = new Patch();
        $shippingAddressPatch->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/transactions/0/item_list/shipping_address',
        ]);
        $shippingAddressPatch->setValue($shippingAddressArray);

        return $shippingAddressPatch;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ShippingAddress;

#[Package('checkout')]
class ShippingAddressPatchBuilder
{
    /**
     * @throws AddressNotFoundException
     */
    public function createShippingAddressPatch(OrderEntity $order): Patch
    {
        $orderShippingAddress = $order->getDeliveries()?->first()?->getShippingOrderAddress();
        if ($orderShippingAddress === null) {
            throw new AddressNotFoundException($order->getDeliveries()?->first()?->getShippingOrderAddressId() ?? '');
        }

        $shippingAddress = new ShippingAddress();

        $shippingAddress->setLine1($orderShippingAddress->getStreet());

        $additionalAddressLine1 = $orderShippingAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $shippingAddress->setLine2($additionalAddressLine1);
        }

        $shippingAddress->setCity($orderShippingAddress->getCity());

        $country = $orderShippingAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $shippingAddress->setCountryCode($countryIso);
            }
        }

        $shippingAddress->setPostalCode($orderShippingAddress->getZipcode() ?? '');

        $state = $orderShippingAddress->getCountryState();
        if ($state !== null) {
            $shippingAddress->setState($state->getShortCode());
        }

        $shippingAddress->setRecipientName(\sprintf('%s %s', $orderShippingAddress->getFirstName(), $orderShippingAddress->getLastName()));
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

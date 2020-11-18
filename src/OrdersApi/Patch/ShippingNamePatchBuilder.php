<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name;
use Swag\PayPal\RestApi\V2\Api\Patch;

class ShippingNamePatchBuilder
{
    /**
     * @throws AddressNotFoundException
     */
    public function createShippingNamePatch(CustomerEntity $customer): Patch
    {
        $customerShippingAddress = $customer->getActiveShippingAddress();
        if ($customerShippingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultShippingAddressId());
        }

        $name = new Name();
        $name->setFullName(\sprintf('%s %s', $customerShippingAddress->getFirstName(), $customerShippingAddress->getLastName()));

        $shippingNameArray = \json_decode((string) \json_encode($name), true);

        $shippingNamePatch = new Patch();
        $shippingNamePatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => "/purchase_units/@reference_id=='default'/shipping/name",
        ]);
        $shippingNamePatch->setValue($shippingNameArray);

        return $shippingNamePatch;
    }
}

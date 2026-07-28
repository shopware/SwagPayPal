<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Util;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Struct\V1\Address;
use Swag\PayPal\AgenticCommerce\Struct\V1\CartItemCollection;
use Swag\PayPal\AgenticCommerce\Struct\V1\PayPalCart;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalCartFactory
{
    public function create(array $data): PayPalCart
    {
        $payPalCart = (new PayPalCart())->assign($data);

        $this->validateCustomerData($payPalCart);

        if (!$payPalCart->isset('items') || !$payPalCart->getItems()->count()) {
            throw AgentException::requiredFieldsMissing('cart.items');
        }

        $this->validateItems($payPalCart->getItems());

        return $payPalCart;
    }

    private function validateCustomerData(PayPalCart $cart): void
    {
        $customer = $cart->getCustomer();
        if (!$customer) {
            return;
        }

        if (!$customer->getEmailAddress()) {
            throw AgentException::requiredFieldsMissing('cart.customer.emailAddress');
        }

        if (!$customer->isset('name') || !$customer->getName()->isset('givenName') || !$customer->getName()->isset('surname')) {
            throw AgentException::requiredFieldsMissing('cart.customer.name');
        }

        $shippingAddress = $cart->getShippingAddress();
        if (!$shippingAddress instanceof Address) {
            throw AgentException::requiredFieldsMissing('cart.shippingAddress');
        }

        $this->validateAddress($shippingAddress);

        if ($cart->getBillingAddress()) {
            $this->validateAddress($cart->getBillingAddress());
        }
    }

    private function validateAddress(Address $address): void
    {
        if (!$address->getAddressLine1()) {
            throw AgentException::requiredFieldsMissing('address.addressLine1');
        }

        if (!$address->getAdminArea2()) {
            throw AgentException::requiredFieldsMissing('address.adminArea2');
        }

        if (!$address->isset('countryCode')) {
            throw AgentException::requiredFieldsMissing('address.countryCode');
        }
    }

    private function validateItems(CartItemCollection $items): void
    {
        foreach ($items as $key => $item) {
            if (!$item->isset('variantId')) {
                throw AgentException::requiredFieldsMissing(\sprintf('cart.items.%s.variantId', $key));
            }

            if (!Uuid::isValid($item->getVariantId() ?? '')) {
                throw AgentException::requiredFieldInvalid(\sprintf('cart.items.%s.variantId', $key), 'Not a valid UUID');
            }

            if (!$item->isset('quantity')) {
                throw AgentException::requiredFieldsMissing(\sprintf('cart.items.%s.quantity', $key));
            }
        }
    }
}

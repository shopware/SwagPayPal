<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Util;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Address;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartItemCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Exception\AgentException;

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
            throw AgentException::requiredFieldsMissing('items');
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
            throw AgentException::requiredFieldsMissing('customer email');
        }

        if (!$customer->isset('name') || !$customer->getName()->isset('givenName') || !$customer->getName()->isset('surname')) {
            throw AgentException::requiredFieldsMissing('customer name');
        }

        $shippingAddress = $cart->getShippingAddress();
        if (!$shippingAddress instanceof Address) {
            throw AgentException::requiredFieldsMissing('shipping address');
        }

        $this->validateAddress($shippingAddress);

        if ($cart->getBillingAddress()) {
            $this->validateAddress($cart->getBillingAddress());
        }
    }

    private function validateAddress(Address $address): void
    {
        if (!$address->getAddressLine1()) {
            throw AgentException::requiredFieldsMissing('address_line_1');
        }

        if (!$address->getAddressLine2()) {
            throw AgentException::requiredFieldsMissing('address_line_2');
        }

        if (!$address->getCountryCode()) {
            throw AgentException::requiredFieldsMissing('country_code');
        }
    }

    private function validateItems(CartItemCollection $items): void
    {
        foreach ($items as $item) {
            if (!$item->isset('variantId')) {
                throw AgentException::requiredFieldsMissing('variant_id');
            }

            if (!Uuid::isValid($item->getVariantId() ?? '')) {
                throw AgentException::requiredFieldsMissing('variant_id not valid uuid');
            }

            if (!$item->isset('quantity')) {
                throw AgentException::requiredFieldsMissing('quantity');
            }
        }
    }
}

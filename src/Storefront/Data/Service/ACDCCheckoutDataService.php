<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Storefront\Data\Struct\ACDC\BillingAddress;
use Swag\PayPal\Storefront\Data\Struct\ACDC\CardholderData;
use Swag\PayPal\Storefront\Data\Struct\ACDCCheckoutData;
use Swag\PayPal\Util\Compatibility\Exception;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;

class ACDCCheckoutDataService extends AbstractCheckoutDataService
{
    public function buildCheckoutData(
        SalesChannelContext $context,
        ?Cart $cart = null,
        ?OrderEntity $order = null
    ): ?ACDCCheckoutData {
        $checkoutData = (new ACDCCheckoutData())->assign($this->getBaseData($context, $order, true));

        $customer = $context->getCustomer();
        if ($customer === null) {
            throw Exception::customerNotLoggedIn();
        }

        $checkoutData->setCardholderData($this->getCardholderData($order ? $order->getBillingAddress() : $customer->getActiveBillingAddress()));

        return $checkoutData;
    }

    public function getMethodDataClass(): string
    {
        return ACDCMethodData::class;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity|null $address
     */
    private function getCardholderData(?Entity $address): CardholderData
    {
        if ($address === null) {
            throw new AddressNotFoundException('billing');
        }

        $data = new CardholderData();

        $data->setCardholderName($address->getFirstName() . ' ' . $address->getLastName());

        $state = $address->getCountryState();
        $country = $address->getCountry();

        $billingAddress = new BillingAddress();
        $billingAddress->setStreetAddress($address->getStreet());
        $billingAddress->setExtendedAddress($address->getAdditionalAddressLine1());
        $billingAddress->setRegion($state ? $state->getName() : '');
        $billingAddress->setLocality($address->getCity());
        $billingAddress->setPostalCode($address->getZipcode() ?? '');
        $billingAddress->setCountryCodeAlpha2($country ? $country->getIso() : '');

        $data->setBillingAddress($billingAddress);

        return $data;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC\Service;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ACDC\ACDCCheckoutFieldData;
use Swag\PayPal\Checkout\ACDC\Struct\BillingAddress;
use Swag\PayPal\Checkout\ACDC\Struct\CardholderData;
use Swag\PayPal\Checkout\APM\Service\AbstractAPMCheckoutDataService;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;

class ACDCCheckoutDataService extends AbstractAPMCheckoutDataService
{
    public function buildCheckoutData(
        SalesChannelContext $context,
        ?OrderEntity $order = null
    ): ACDCCheckoutFieldData {
        $checkoutData = (new ACDCCheckoutFieldData())->assign($this->getBaseData($context, $order));

        $customer = $context->getCustomer();
        if ($customer === null) {
            throw new CustomerNotLoggedInException();
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
        $billingAddress->setPostalCode($address->getZipcode());
        $billingAddress->setCountryCodeAlpha2($country ? $country->getIso() : '');

        $data->setBillingAddress($billingAddress);

        return $data;
    }
}

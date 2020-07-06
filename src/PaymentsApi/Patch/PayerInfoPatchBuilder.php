<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Swag\PayPal\PayPal\ApiV1\Api\Patch;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Payer\PayerInfo;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Payer\PayerInfo\BillingAddress;

class PayerInfoPatchBuilder
{
    /**
     * @throws AddressNotFoundException
     */
    public function createPayerInfoPatch(CustomerEntity $customer): Patch
    {
        $customerBillingAddress = $customer->getActiveBillingAddress();
        if ($customerBillingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultBillingAddressId());
        }

        $payerInfo = new PayerInfo();
        $payerInfo->setEmail($customer->getEmail());
        $payerInfo->setFirstName($customerBillingAddress->getFirstName());
        $payerInfo->setLastName($customerBillingAddress->getLastName());
        $payerInfo->setBillingAddress($this->createBillingAddress($customerBillingAddress));

        $payerInfoArray = \json_decode((string) \json_encode($payerInfo), true);

        $payerInfoPatch = new Patch();
        $payerInfoPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/payer/payer_info',
        ]);
        $payerInfoPatch->setValue($payerInfoArray);

        return $payerInfoPatch;
    }

    private function createBillingAddress(CustomerAddressEntity $customerBillingAddress): BillingAddress
    {
        $billingAddress = new BillingAddress();

        $billingAddress->setLine1($customerBillingAddress->getStreet());

        $additionalAddressLine1 = $customerBillingAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $billingAddress->setLine2($additionalAddressLine1);
        }

        $billingAddress->setCity($customerBillingAddress->getCity());

        $country = $customerBillingAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $billingAddress->setCountryCode($countryIso);
            }
        }

        $billingAddress->setPostalCode($customerBillingAddress->getZipcode());

        $state = $customerBillingAddress->getCountryState();
        if ($state !== null) {
            $billingAddress->setState($state->getShortCode());
        }

        $phoneNumber = $customerBillingAddress->getPhoneNumber();
        if ($phoneNumber !== null) {
            $billingAddress->setPhone($phoneNumber);
        }

        return $billingAddress;
    }
}

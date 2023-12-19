<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Common\Address;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\PayerInfo;

#[Package('checkout')]
class PayerInfoPatchBuilder
{
    /**
     * @throws AddressNotFoundException
     */
    public function createPayerInfoPatch(OrderEntity $order): Patch
    {
        $orderBillingAddress = $order->getBillingAddress();
        if ($orderBillingAddress === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        $customer = $order->getOrderCustomer();
        if ($customer === null) {
            throw PaymentException::invalidOrder($order->getId());
        }

        $payerInfo = new PayerInfo();
        $payerInfo->setEmail($customer->getEmail());
        $payerInfo->setFirstName($orderBillingAddress->getFirstName());
        $payerInfo->setLastName($orderBillingAddress->getLastName());
        $payerInfo->setBillingAddress($this->createBillingAddress($orderBillingAddress));

        $payerInfoArray = \json_decode((string) \json_encode($payerInfo), true);

        $payerInfoPatch = new Patch();
        $payerInfoPatch->assign([
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/payer/payer_info',
        ]);
        $payerInfoPatch->setValue($payerInfoArray);

        return $payerInfoPatch;
    }

    private function createBillingAddress(OrderAddressEntity $orderBillingAddress): Address
    {
        $billingAddress = new Address();

        $billingAddress->setLine1($orderBillingAddress->getStreet());

        $additionalAddressLine1 = $orderBillingAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $billingAddress->setLine2($additionalAddressLine1);
        }

        $billingAddress->setCity($orderBillingAddress->getCity());

        $country = $orderBillingAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $billingAddress->setCountryCode($countryIso);
            }
        }

        $billingAddress->setPostalCode($orderBillingAddress->getZipcode() ?? '');

        $state = $orderBillingAddress->getCountryState();
        if ($state !== null) {
            $billingAddress->setState($state->getShortCode());
        }

        $phoneNumber = $orderBillingAddress->getPhoneNumber();
        if ($phoneNumber !== null) {
            $billingAddress->setPhone($phoneNumber);
        }

        return $billingAddress;
    }
}

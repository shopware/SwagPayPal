<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\PUI\Exception\MissingBirthdayException;
use Swag\PayPal\Checkout\PUI\Exception\MissingPhoneNumberException;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Common\PhoneNumber;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class PUIOrderBuilder extends AbstractAPMOrderBuilder
{
    protected function buildPaymentSource(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void {
        $orderCustomer = $paymentTransaction->getOrder()->getOrderCustomer();
        if ($orderCustomer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $payUponInvoice = new PayUponInvoice();
        $payUponInvoice->setEmail($orderCustomer->getEmail());

        $name = new Name();
        $name->setGivenName($orderCustomer->getFirstName());
        $name->setSurname($orderCustomer->getLastName());

        $orderAddress = $paymentTransaction->getOrder()->getBillingAddress();
        if ($orderAddress === null) {
            throw new AddressNotFoundException($paymentTransaction->getOrder()->getBillingAddressId());
        }
        $address = new Address();
        $this->addressProvider->createAddress($orderAddress, $address);

        $experienceContext = $this->createExperienceContext($salesChannelContext, $paymentTransaction);
        $experienceContext->setCustomerServiceInstructions([
            $this->systemConfigService->getString(Settings::PUI_CUSTOMER_SERVICE_INSTRUCTIONS, $salesChannelContext->getSalesChannelId()),
        ]);

        $payUponInvoice->setName($name);
        $payUponInvoice->setEmail($orderCustomer->getEmail());
        $payUponInvoice->setBirthDate($this->getBirthday($salesChannelContext));
        $payUponInvoice->setPhone($this->getPhoneNumber($orderAddress));
        $payUponInvoice->setBillingAddress($address);
        $payUponInvoice->setExperienceContext($experienceContext);

        $paymentSource->setPayUponInvoice($payUponInvoice);
    }

    protected function submitCart(SalesChannelContext $salesChannelContext): bool
    {
        return true;
    }

    private function getPhoneNumber(OrderAddressEntity $orderAddress): PhoneNumber
    {
        $phoneNumber = $orderAddress->getPhoneNumber();
        if (!$phoneNumber) {
            throw new MissingPhoneNumberException($orderAddress->getId());
        }

        $countryCodeMatches = [];
        $countryCode = '49';
        \preg_match('/^(\+|00)(\d{1,3})\s+/', $phoneNumber, $countryCodeMatches);
        if (!empty($countryCodeMatches) && isset($countryCodeMatches[2])) {
            $countryCode = $countryCodeMatches[2];
        }

        $phoneNumber = \preg_replace('/(^((\+|00)\d{1,3}\s+|0049|49)|\D)/', '', $phoneNumber) ?? '';

        $phone = new PhoneNumber();
        $phone->setNationalNumber($phoneNumber);
        $phone->setCountryCode($countryCode);

        return $phone;
    }

    private function getBirthday(SalesChannelContext $salesChannelContext): string
    {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $birthday = $customer->getBirthday();
        if (!$birthday) {
            throw new MissingBirthdayException($customer->getId());
        }

        return $birthday->format('Y-m-d');
    }
}

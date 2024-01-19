<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\PUI\Exception\MissingBirthdayException;
use Swag\PayPal\Checkout\PUI\Exception\MissingPhoneNumberException;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Common\PhoneNumber;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;

#[Package('checkout')]
class PUIOrderBuilder extends AbstractOrderBuilder
{
    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        PurchaseUnitProvider $purchaseUnitProvider,
        AddressProvider $addressProvider,
        private readonly ItemListProvider $itemListProvider,
        LocaleCodeProvider $localeCodeProvider
    ) {
        parent::__construct($systemConfigService, $purchaseUnitProvider, $addressProvider, $localeCodeProvider);
    }

    public function getOrder(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $isNet = $paymentTransaction->getOrder()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS;
        $purchaseUnit = $this->purchaseUnitProvider->createPurchaseUnit(
            $paymentTransaction->getOrderTransaction()->getAmount(),
            $paymentTransaction->getOrder()->getShippingCosts(),
            $customer,
            $this->itemListProvider->getItemList($salesChannelContext->getCurrency(), $paymentTransaction->getOrder()),
            $salesChannelContext,
            $isNet,
            $paymentTransaction->getOrder(),
            $paymentTransaction->getOrderTransaction()
        );

        $paymentSource = $this->getPaymentSource(
            $this->getOrderCustomer($paymentTransaction->getOrder()),
            $this->getBillingAddress($paymentTransaction->getOrder()),
            $customer,
            $salesChannelContext
        );

        $order = new Order();
        $order->setIntent(PaymentIntentV2::CAPTURE);
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        $order->setProcessingInstruction(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL);
        $order->setPaymentSource($paymentSource);

        return $order;
    }

    private function getPaymentSource(
        OrderCustomerEntity $orderCustomer,
        OrderAddressEntity $orderAddress,
        CustomerEntity $customer,
        SalesChannelContext $salesChannelContext
    ): PaymentSource {
        $payUponInvoice = new PayUponInvoice();
        $payUponInvoice->setEmail($orderCustomer->getEmail());

        $name = new Name();
        $name->setGivenName($orderCustomer->getFirstName());
        $name->setSurname($orderCustomer->getLastName());

        $address = new Address();
        $this->addressProvider->createAddress($orderAddress, $address);

        $experienceContext = new ExperienceContext();
        $experienceContext->setBrandName($this->getBrandName($salesChannelContext));
        $experienceContext->setLocale($this->localeCodeProvider->getLocaleCodeFromContext($salesChannelContext->getContext()));
        $experienceContext->setCustomerServiceInstructions([
            $this->systemConfigService->getString(Settings::PUI_CUSTOMER_SERVICE_INSTRUCTIONS, $salesChannelContext->getSalesChannelId()),
        ]);

        $payUponInvoice->setName($name);
        $payUponInvoice->setEmail($orderCustomer->getEmail());
        $payUponInvoice->setBirthDate($this->getBirthday($customer));
        $payUponInvoice->setPhone($this->getPhoneNumber($orderAddress));
        $payUponInvoice->setBillingAddress($address);
        $payUponInvoice->setExperienceContext($experienceContext);

        $paymentSource = new PaymentSource();
        $paymentSource->setPayUponInvoice($payUponInvoice);

        return $paymentSource;
    }

    private function getOrderCustomer(OrderEntity $order): OrderCustomerEntity
    {
        $customer = $order->getOrderCustomer();
        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        return $customer;
    }

    private function getBillingAddress(OrderEntity $order): OrderAddressEntity
    {
        $address = $order->getBillingAddress();
        if ($address === null) {
            throw new AddressNotFoundException($order->getBillingAddressId());
        }

        return $address;
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

    private function getBirthday(CustomerEntity $customer): string
    {
        $birthday = $customer->getBirthday();
        if (!$birthday) {
            throw new MissingBirthdayException($customer->getId());
        }

        return $birthday->format('Y-m-d');
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Address as ShippingAddress;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name as ShippingName;
use Swag\PayPal\Setting\Settings;

class PurchaseUnitProvider
{
    private AmountProvider $amountProvider;

    private SystemConfigService $systemConfigService;

    public function __construct(AmountProvider $amountProvider, SystemConfigService $systemConfigService)
    {
        $this->amountProvider = $amountProvider;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param Item[]|null $itemList
     */
    public function createPurchaseUnit(
        CalculatedPrice $totalAmount,
        CalculatedPrice $shippingCosts,
        ?CustomerEntity $customer,
        ?array $itemList,
        SalesChannelContext $salesChannelContext,
        bool $isNet,
        ?OrderEntity $order = null,
        ?OrderTransactionEntity $orderTransaction = null
    ): PurchaseUnit {
        $purchaseUnit = new PurchaseUnit();

        if ($itemList !== null) {
            $purchaseUnit->setItems($itemList);
        }

        $amount = $this->amountProvider->createAmount(
            $totalAmount,
            $shippingCosts,
            $salesChannelContext->getCurrency(),
            $purchaseUnit,
            $isNet
        );

        $purchaseUnit->setAmount($amount);

        if ($customer !== null) {
            $purchaseUnit->setShipping($this->createShipping($customer));
        }

        if ($orderTransaction !== null) {
            $purchaseUnit->setCustomId($orderTransaction->getId());
        }

        $orderNumber = $order !== null ? $order->getOrderNumber() : null;

        if ($orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelContext->getSalesChannelId())) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelContext->getSalesChannelId());
            $orderNumber = $orderNumberPrefix . $orderNumber;
            $purchaseUnit->setInvoiceId($orderNumber);
        }

        return $purchaseUnit;
    }

    private function createShipping(CustomerEntity $customer): Shipping
    {
        $shippingAddress = $customer->getActiveShippingAddress();
        if ($shippingAddress === null) {
            throw new AddressNotFoundException($customer->getDefaultShippingAddressId());
        }

        $shipping = new Shipping();

        /** @var ShippingAddress $address */
        $address = $this->createAddress($shippingAddress, new ShippingAddress());
        $shipping->setAddress($address);
        $shipping->setName($this->createShippingName($shippingAddress));

        return $shipping;
    }

    private function createAddress(CustomerAddressEntity $customerAddress, Address $address): Address
    {
        $address->setAddressLine1($customerAddress->getStreet());

        $additionalAddressLine1 = $customerAddress->getAdditionalAddressLine1();
        if ($additionalAddressLine1 !== null) {
            $address->setAddressLine2($additionalAddressLine1);
        }

        $state = $customerAddress->getCountryState();
        if ($state !== null) {
            $address->setAdminArea1($state->getShortCode());
        }

        $address->setAdminArea2($customerAddress->getCity());
        $address->setPostalCode($customerAddress->getZipcode());

        $country = $customerAddress->getCountry();
        if ($country !== null) {
            $countryIso = $country->getIso();
            if ($countryIso !== null) {
                $address->setCountryCode($countryIso);
            }
        }

        return $address;
    }

    private function createShippingName(CustomerAddressEntity $shippingAddress): ShippingName
    {
        $shippingName = new ShippingName();
        $shippingName->setFullName(\sprintf('%s %s', $shippingAddress->getFirstName(), $shippingAddress->getLastName()));

        return $shippingName;
    }
}

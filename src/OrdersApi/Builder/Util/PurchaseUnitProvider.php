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
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Address as ShippingAddress;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Name as ShippingName;
use Swag\PayPal\Setting\Settings;

class PurchaseUnitProvider
{
    private AmountProvider $amountProvider;

    private AddressProvider $addressProvider;

    private CustomIdProvider $customIdProvider;

    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        AmountProvider $amountProvider,
        AddressProvider $addressProvider,
        CustomIdProvider $customIdProvider,
        SystemConfigService $systemConfigService
    ) {
        $this->amountProvider = $amountProvider;
        $this->addressProvider = $addressProvider;
        $this->customIdProvider = $customIdProvider;
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
            $purchaseUnit->setCustomId($this->customIdProvider->createCustomId($orderTransaction, $salesChannelContext->getContext()));
        }

        $orderNumber = $order !== null ? $order->getOrderNumber() : null;

        if ($orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $salesChannelContext->getSalesChannelId())) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $salesChannelContext->getSalesChannelId());
            $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $salesChannelContext->getSalesChannelId());
            $orderNumber = $orderNumberPrefix . $orderNumber . $orderNumberSuffix;
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

        $address = new ShippingAddress();
        $this->addressProvider->createAddress($shippingAddress, $address);
        $shipping->setAddress($address);
        $shipping->setName($this->createShippingName($shippingAddress));

        return $shipping;
    }

    private function createShippingName(CustomerAddressEntity $shippingAddress): ShippingName
    {
        $shippingName = new ShippingName();
        $shippingName->setFullName(\sprintf('%s %s', $shippingAddress->getFirstName(), $shippingAddress->getLastName()));

        return $shippingName;
    }
}

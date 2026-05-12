<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
trait PaymentTransactionTrait
{
    use StateMachineStateTrait;

    protected function createOrderTransaction(?string $transactionId = null): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setOrderId(ConstantsForTesting::TEST_ORDER_ID);

        if ($transactionId === null) {
            $transactionId = Uuid::randomHex();
        }
        $orderTransaction->setId($transactionId);

        $amount = $this->createPriceStruct();
        $orderTransaction->setAmount($amount);

        return $orderTransaction;
    }

    protected function createOrderEntity(string $orderId, ?string $orderNumber = null): OrderEntity
    {
        $orderNumber = $orderNumber ?? ConstantsForTesting::TEST_ORDER_NUMBER_WITHOUT_PREFIX;
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setShippingCosts(new CalculatedPrice(4.99, 4.99, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $order->setId($orderId);
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $order->setSalesChannel($salesChannel);
        $currency = $this->createCurrencyEntity();
        $order->setCurrency($currency);
        $order->setOrderNumber($orderNumber);
        $order->setPrice(new CartPrice(
            722.69,
            860.0,
            722.69,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    137.31,
                    19.0,
                    722.6890756302521
                ),
            ]),
            new TaxRuleCollection([
                new TaxRule(
                    19.0,
                    100.0
                ),
            ]),
            CartPrice::TAX_STATE_NET
        ));
        $order->setAmountNet(722.69);
        $order->setAmountTotal(860.0);
        $order->setTaxStatus(CartPrice::TAX_STATE_GROSS);
        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setType('product');
        $lineItem->setIdentifier('test');
        $lineItem->setQuantity(1);
        $lineItem->setLabel('test');
        $lineItem->setUnitPrice(5.0);
        $lineItem->setTotalPrice(5.0);
        $lineItem->setPrice(new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection()));
        $lineItem->setGood(true);
        $order->setLineItems(new OrderLineItemCollection([$lineItem]));

        switch ($orderId) {
            case ConstantsForTesting::VALID_ORDER_ID:
                $order->setId(ConstantsForTesting::VALID_ORDER_ID);
                $order->setLineItems($this->getLineItems());

                break;
            case ConstantsForTesting::ORDER_ID_MISSING_PRICE:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_PRICE);
                $order->setLineItems($this->getLineItems());

                break;
            default:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);
        }

        $country = new CountryEntity();
        $country->setIso('DE');
        $state = new CountryStateEntity();
        $state->setShortCode('NRW');
        $address = new OrderAddressEntity();
        $address->setFirstName('Some');
        $address->setLastName('One');
        $address->setStreet('Street 1');
        $address->setZipcode('12345');
        $address->setCity('City');
        $address->setPhoneNumber('+41 (0123) 49567-89'); // extra weird for filter testing
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);
        $address->setCountryState($state);
        $order->setBillingAddress($address);
        $order->setBillingAddressId($address->getId());

        $delivery = new OrderDeliveryEntity();
        $delivery->setId(Uuid::randomHex());
        $address = new OrderAddressEntity();
        $address->setFirstName('FirstName');
        $address->setLastName('LastName');
        $address->setStreet('Street 1');
        $address->setAdditionalAddressLine1('Test address line 1');
        $address->setZipcode('12345');
        $address->setCity('City');
        $address->setPhoneNumber('+41 (0123) 49567-89'); // extra weird for filter testing
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);
        $address->setCountryState($state);
        $delivery->setShippingOrderAddress($address);
        $order->setDeliveries(new OrderDeliveryCollection([$delivery]));

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setBirthday(new \DateTime('-30 years'));

        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setFirstName('Test');
        $orderCustomer->setLastName('Customer');
        $orderCustomer->setEmail('test@test.com');
        $orderCustomer->setCustomer($customer);
        $order->setOrderCustomer($orderCustomer);

        return $order;
    }

    private function createPriceStruct(): CalculatedPrice
    {
        return new CalculatedPrice(
            722.69,
            860.0,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    137.31,
                    19.0,
                    722.69
                ),
            ]),
            new TaxRuleCollection([
                new TaxRule(
                    19.0,
                    100.0
                ),
            ]),
            1
        );
    }

    private function createCurrencyEntity(): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode(ConstantsForTesting::EXPECTED_ITEM_CURRENCY);

        return $currency;
    }

    private function getLineItems(): OrderLineItemCollection
    {
        $orderLineItem = new OrderLineItemEntity();

        $orderLineItem->setId('6198ff79c4144931919977829dbca3d6');
        $orderLineItem->setQuantity(ConstantsForTesting::EXPECTED_ITEM_QUANTITY);
        $orderLineItem->setUnitPrice(855.01);
        $orderLineItem->setTotalPrice($orderLineItem->getUnitPrice() * $orderLineItem->getQuantity());

        $orderLineItem->setLabel(ConstantsForTesting::EXPECTED_ITEM_NAME);
        $orderLineItem->setPayload(['productNumber' => ConstantsForTesting::EXPECTED_PRODUCT_NUMBER]);

        return new OrderLineItemCollection([$orderLineItem]);
    }
}

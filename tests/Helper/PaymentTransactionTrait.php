<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Test\Payment\Builder\OrderPaymentBuilderTest;

trait PaymentTransactionTrait
{
    protected function createPaymentTransactionStruct(
        string $orderId = 'some-order-id',
        ?string $transactionId = null
    ): AsyncPaymentTransactionStruct {
        $orderTransaction = $this->createOrderTransaction($transactionId);
        $order = $this->createOrderEntity($orderId);

        return new AsyncPaymentTransactionStruct(
            $orderTransaction,
            $order,
            'http://www.test.de/'
        );
    }

    private function createOrderTransaction(?string $transactionId): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setOrderId(OrderPaymentBuilderTest::TEST_ORDER_ID);

        if ($transactionId === null) {
            $transactionId = Uuid::randomHex();
        }
        $orderTransaction->setId($transactionId);

        $amount = $this->createPriceStruct();
        $orderTransaction->setAmount($amount);

        return $orderTransaction;
    }

    private function createOrderEntity(string $orderId): OrderEntity
    {
        $order = new OrderEntity();
        $order->setShippingCosts(new CalculatedPrice(4.99, 4.99, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $order->setId($orderId);
        $currency = $this->createCurrencyEntity();
        $order->setCurrency($currency);
        $order->setOrderNumber(OrderPaymentBuilderTest::TEST_ORDER_NUMBER);
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

        switch ($orderId) {
            case ConstantsForTesting::VALID_ORDER_ID:
                $order->setId(ConstantsForTesting::VALID_ORDER_ID);
                $order->setLineItems($this->getLineItems(true));
                break;
            case ConstantsForTesting::ORDER_ID_MISSING_PRICE:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_PRICE);
                $order->setLineItems($this->getLineItems());
                break;
            default:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);
        }

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
        $currency->setShortName(OrderPaymentBuilderTest::EXPECTED_ITEM_CURRENCY);

        return $currency;
    }

    private function getLineItems(bool $setPrice = false): OrderLineItemCollection
    {
        $orderLineItem = new OrderLineItemEntity();

        $orderLineItem->setId('6198ff79c4144931919977829dbca3d6');
        $orderLineItem->setQuantity(OrderPaymentBuilderTest::EXPECTED_ITEM_QUANTITY);

        if ($setPrice) {
            $orderLineItem->setPrice(
                new CalculatedPrice(
                    855.01,
                    855.01,
                    new CalculatedTaxCollection([
                        new CalculatedTax(OrderPaymentBuilderTest::EXPECTED_ITEM_TAX, 19, 722.69),
                    ]),
                    new TaxRuleCollection([19 => new TaxRule(19)])
                )
            );
        }

        $orderLineItem->setLabel(OrderPaymentBuilderTest::EXPECTED_ITEM_NAME);
        $orderLineItem->setPayload(['productNumber' => OrderPaymentBuilderTest::EXPECTED_PRODUCT_NUMBER]);

        return new OrderLineItemCollection([$orderLineItem]);
    }
}

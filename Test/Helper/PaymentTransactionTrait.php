<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagPayPal\SwagPayPal;

trait PaymentTransactionTrait
{
    protected function createPaymentTransactionStruct(string $orderId = 'some-order-id'): PaymentTransactionStruct
    {
        $transactionId = Uuid::uuid4()->getHex();
        $order = $this->createOrderEntity($orderId);
        $amount = $this->createPriceStruct();

        return new PaymentTransactionStruct(
            $transactionId,
            'paypal-payment-method-id',
            $order,
            $amount,
            'http://www.test.de/'
        );
    }

    private function createOrderEntity(string $id): OrderEntity
    {
        $order = new OrderEntity();
        $order->setShippingCosts(new CalculatedPrice(2.5, 2.5, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $order->setId($id);
        $currency = $this->createCurrencyEntity();
        $order->setCurrency($currency);

        return $order;
    }

    private function createPriceStruct(): CalculatedPrice
    {
        return new CalculatedPrice(
            1.5,
            3.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            2
        );
    }

    private function createCurrencyEntity(): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setShortName('EUR');

        return $currency;
    }
}

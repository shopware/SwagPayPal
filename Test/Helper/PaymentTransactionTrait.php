<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Price\Struct\Price;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\OrderStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Currency\CurrencyStruct;
use SwagPayPal\SwagPayPal;

trait PaymentTransactionTrait
{
    protected function createPaymentTransactionStruct(): PaymentTransactionStruct
    {
        $transactionId = Uuid::uuid4()->getHex();
        $order = $this->createOrderStruct();
        $amount = $this->createPriceStruct();

        return new PaymentTransactionStruct(
            $transactionId,
            SwagPayPal::PAYMENT_METHOD_PAYPAL_ID,
            $order,
            $amount,
            'http://www.test.de/'
        );
    }

    private function createOrderStruct(): OrderStruct
    {
        $order = new OrderStruct();
        $order->setShippingTotal(2.5);
        $currency = $this->createCurrencyStruct();
        $order->setCurrency($currency);

        return $order;
    }

    private function createPriceStruct(): Price
    {
        return new Price(
            1.5,
            3.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            2
        );
    }

    private function createCurrencyStruct(): CurrencyStruct
    {
        $currency = new CurrencyStruct();
        $currency->setShortName('EUR');

        return $currency;
    }
}

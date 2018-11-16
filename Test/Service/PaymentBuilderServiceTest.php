<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\Price;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\OrderStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Currency\CurrencyStruct;
use SwagPayPal\Service\PaymentBuilderService;
use SwagPayPal\SwagPayPal;
use SwagPayPal\Test\Mock\Repositories\LanguageRepositoryMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepositoryMock;

class PaymentBuilderServiceTest extends TestCase
{
    private const CURRENCY_CODE = 'EUR';

    private const SHOP_URL = 'http://www.test.de/';

    private const SHIPPING_COSTS = 2.5;

    private const PRODUCT_UNIT_PRICE = 1.5;

    private const PRODUCT_TOTAL_PRICE = 3.0;

    private const PRODUCT_QUANTITY = 2;

    public function testGetPayment(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);

        self::assertSame(
            SalesChannelRepositoryMock::SALES_CHANNEL_NAME,
            $payment->getApplicationContext()->getBrandName()
        );
        self::assertSame(LanguageRepositoryMock::LOCALE_CODE, $payment->getApplicationContext()->getLocale());
        self::assertSame(self::CURRENCY_CODE, $payment->getTransactions()->getAmount()->getCurrency());
        self::assertSame(self::SHOP_URL, $payment->getRedirectUrls()->getReturnUrl());
        self::assertSame(self::SHOP_URL . '&cancel=1', $payment->getRedirectUrls()->getCancelUrl());
        self::assertSame(self::SHIPPING_COSTS, $payment->getTransactions()->getAmount()->getDetails()->getShipping());
        self::assertSame(
            self::PRODUCT_TOTAL_PRICE,
            $payment->getTransactions()->getAmount()->getDetails()->getSubTotal()
        );
    }

    private function createPaymentBuilder(): PaymentBuilderService
    {
        return new PaymentBuilderService(
            new LanguageRepositoryMock(),
            new SalesChannelRepositoryMock()
        );
    }

    /**
     * @return PaymentTransactionStruct
     */
    private function createPaymentTransactionStruct(): PaymentTransactionStruct
    {
        $transactionId = Uuid::uuid4()->getHex();
        $order = $this->createOrderStruct();
        $amount = $this->createPriceStruct();

        return new PaymentTransactionStruct(
            $transactionId,
            SwagPayPal::PAYMENT_METHOD_PAYPAL_ID,
            $order,
            $amount,
            self::SHOP_URL
        );
    }

    private function createOrderStruct(): OrderStruct
    {
        $order = new OrderStruct();
        $order->setShippingTotal(self::SHIPPING_COSTS);
        $currency = $this->createCurrencyStruct();
        $order->setCurrency($currency);

        return $order;
    }

    private function createPriceStruct(): Price
    {
        return new Price(
            self::PRODUCT_UNIT_PRICE,
            self::PRODUCT_TOTAL_PRICE,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            self::PRODUCT_QUANTITY
        );
    }

    /**
     * @return CurrencyStruct
     */
    private function createCurrencyStruct(): CurrencyStruct
    {
        $currency = new CurrencyStruct();
        $currency->setShortName(self::CURRENCY_CODE);

        return $currency;
    }
}

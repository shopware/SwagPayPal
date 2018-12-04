<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Payment\PaymentBuilderService;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Mock\Repositories\LanguageRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;

class PaymentBuilderServiceTest extends TestCase
{
    use PaymentTransactionTrait;

    public function testGetPayment(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);

        self::assertInstanceOf(Payment::class, $payment);

        $transaction = $payment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
    }

    public function testGetPaymentWithItemList(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);

        $itemList = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($itemList);
        if (!$itemList) {
            return;
        }

        $itemList = json_decode($itemList, true);
        $item = $itemList['item_list']['items'][0];

        self::assertSame(OrderRepoMock::EXPECTED_ITEM_NAME, $item['name']);
        self::assertSame(OrderRepoMock::EXPECTED_ITEM_CURRENCY, $item['currency']);
        self::assertSame(OrderRepoMock::EXPECTED_ITEM_PRICE, $item['price']);
        self::assertSame(OrderRepoMock::EXPECTED_ITEM_QUANTITY, $item['quantity']);
        self::assertSame(OrderRepoMock::EXPECTED_ITEM_SKU, $item['sku']);
        self::assertSame(OrderRepoMock::EXPECTED_ITEM_TAX, $item['tax']);
    }

    public function testGetPaymentWithoutPrice(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_PRICE);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);
        $itemList = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($itemList);
        if (!$itemList) {
            return;
        }

        $itemList = json_decode($itemList, true)['item_list'];

        self::assertNull($itemList);
    }

    public function testGetPaymentWithoutLineItems(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();
        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);
        $itemList = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($itemList);
        if (!$itemList) {
            return;
        }

        $itemList = json_decode($itemList, true)['item_list'];

        self::assertNull($itemList);
    }

    private function createPaymentBuilder(): PaymentBuilderService
    {
        return new PaymentBuilderService(
            new LanguageRepoMock(),
            new SalesChannelRepoMock(),
            new OrderRepoMock(),
            new SettingsProviderMock()
        );
    }
}

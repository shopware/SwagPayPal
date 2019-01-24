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
use Shopware\Core\Framework\SourceContext;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Api\Payment\ApplicationContext;
use SwagPayPal\PayPal\Exception\PayPalSettingsInvalidException;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\Repositories\OrderRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;

class PaymentBuilderServiceTest extends TestCase
{
    use PaymentTransactionTrait,
        ServicesTrait;

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

    public function testGetPaymentInvalidIntentThrowsException(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_INVALID_INTENT, new Entity());

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $paymentBuilder->getPayment($paymentTransaction, $context);
    }

    public function testGetPaymentWithoutBrandName(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITHOUT_BRAND_NAME, new Entity());

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $context));
        self::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        self::assertSame(SalesChannelRepoMock::SALES_CHANNEL_NAME, $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithoutBrandNameAndSalesChannel(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = $this->createContextWithoutSalesChannel();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITHOUT_BRAND_NAME, new Entity());

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $context));
        self::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        self::assertSame('', $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithItemList(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);

        $transaction = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($transaction);
        if ($transaction === false) {
            return;
        }

        $transaction = json_decode($transaction, true);
        $item = $transaction['item_list']['items'][0];

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
        $transaction = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($transaction);
        if ($transaction === false) {
            return;
        }

        $transaction = json_decode($transaction, true)['item_list'];

        self::assertNull($transaction);
    }

    public function testGetPaymentWithoutLineItems(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();
        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);
        $transaction = json_encode($payment->getTransactions()[0]);

        self::assertNotFalse($transaction);
        if ($transaction === false) {
            return;
        }

        $transaction = json_decode($transaction, true)['item_list'];

        self::assertNull($transaction);
    }

    /**
     * @dataProvider dataProviderTestApplicationContext
     */
    public function testApplicationContext(string $extensionName, string $expectedResult): void
    {
        $paymentBuilder = $this->createPaymentBuilder();
        $context = Context::createDefaultContext();
        $context->addExtension($extensionName, new Entity());
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);
        $paymentJsonString = json_encode($payment);
        self::assertNotFalse($paymentJsonString);
        if ($paymentJsonString === false) {
            return;
        }

        $applicationContext = json_decode($paymentJsonString, true)['application_context'];
        self::assertSame($expectedResult, $applicationContext['landing_page']);
    }

    public function dataProviderTestApplicationContext(): array
    {
        return [
            [
                SettingsProviderMock::PAYPAL_SETTING_WITHOUT_TOKEN,
                ApplicationContext::LANDINGPAGE_TYPE_BILLING,
            ],
            [
                SettingsProviderMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID,
                ApplicationContext::LANDINGPAGE_TYPE_LOGIN,
            ],
            [
                SettingsProviderMock::PAYPAL_SETTING_WITH_SUBMIT_CART,
                ApplicationContext::LANDINGPAGE_TYPE_LOGIN,
            ],
        ];
    }

    private function createContextWithoutSalesChannel(): Context
    {
        $defaultContext = Context::createDefaultContext();
        $sourceContext = new SourceContext();
        $sourceContext->setSalesChannelId('foo');

        return new Context(
            $sourceContext,
            $defaultContext->getCatalogIds(),
            $defaultContext->getRules(),
            $defaultContext->getCurrencyId(),
            $defaultContext->getLanguageIdChain(),
            $defaultContext->getVersionId(),
            $defaultContext->getCurrencyFactor()
        );
    }
}

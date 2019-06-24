<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Payment\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Context\SalesChannelApiSource;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;

class OrderPaymentBuilderTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;

    public const TEST_ORDER_NUMBER = 'SW1234';
    public const TEST_ORDER_ID = 'test-order-id';
    public const EXPECTED_ITEM_NAME = 'Aerodynamic Paper Ginger Vitro';
    public const EXPECTED_PRODUCT_NUMBER = '0716562764cd43389abe16faad1838b8';
    public const EXPECTED_ITEM_CURRENCY = 'EUR';
    public const EXPECTED_ITEM_TAX = 0;
    public const EXPECTED_ITEM_QUANTITY = 1;

    public function testGetPayment(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        static::assertInstanceOf(Payment::class, $payment);

        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
    }

    public function testGetPaymentInvalidIntentThrowsException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setIntent('invalid');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
    }

    public function testGetPaymentWithoutBrandName(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setBrandName('');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        static::assertSame(SalesChannelRepoMock::SALES_CHANNEL_NAME, $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithoutBrandNameAndSalesChannel(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setBrandName('');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = $this->createContextWithoutSalesChannel();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        static::assertSame('', $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithItemList(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSubmitCart(true);
        $settings->setLandingPage('Foo');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        $transaction = json_encode($payment->getTransactions()[0]);

        static::assertNotFalse($transaction);
        if ($transaction === false) {
            return;
        }

        $transaction = json_decode($transaction, true);
        static::assertNotNull(
            $transaction['item_list'],
            'ItemList is null, it probably got removed by the TransactionValidator.'
        );
        $item = $transaction['item_list']['items'][0];

        static::assertSame(self::EXPECTED_ITEM_NAME, $item['name']);
        static::assertSame(self::EXPECTED_ITEM_CURRENCY, $item['currency']);
        static::assertSame('855.01', $item['price']);
        static::assertSame(self::EXPECTED_ITEM_QUANTITY, $item['quantity']);
        static::assertSame(self::EXPECTED_PRODUCT_NUMBER, $item['sku']);
        static::assertSame((string) self::EXPECTED_ITEM_TAX, $item['tax']);
    }

    public function testGetPaymentWithoutPrice(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSubmitCart(true);
        $settings->setLandingPage('Foo');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_PRICE);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $transaction = json_encode($payment->getTransactions()[0]);

        static::assertNotFalse($transaction);
        if ($transaction === false) {
            return;
        }

        $transaction = json_decode($transaction, true)['item_list'];

        static::assertNull($transaction);
    }

    public function testGetPaymentWithoutLineItems(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSubmitCart(true);
        $settings->setLandingPage('Foo');
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('The order with id order-id-missing-line-items is invalid or could not be found.');
        $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
    }

    /**
     * @dataProvider dataProviderTestApplicationContext
     */
    public function testApplicationContext(SwagPayPalSettingStruct $settings, string $expectedResult): void
    {
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext(new SalesChannelApiSource(Defaults::SALES_CHANNEL));
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $paymentJsonString = json_encode($payment);
        static::assertNotFalse($paymentJsonString);
        if ($paymentJsonString === false) {
            return;
        }

        $applicationContext = json_decode($paymentJsonString, true)['application_context'];
        static::assertSame($expectedResult, $applicationContext['landing_page']);
        static::assertSame('en-GB', $applicationContext['locale']);
        static::assertSame('commit', $applicationContext['user_action']);
    }

    public function dataProviderTestApplicationContext(): array
    {
        $withoutToken = $this->createDefaultSettingStruct();
        $withoutToken->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $withoutToken->setLandingPage(ApplicationContext::LANDINGPAGE_TYPE_BILLING);

        $withoutTokenAndId = $this->createDefaultSettingStruct();
        $withoutTokenAndId->setLandingPage(ApplicationContext::LANDINGPAGE_TYPE_LOGIN);

        $submitCart = $this->createDefaultSettingStruct();
        $submitCart->setSubmitCart(true);
        $submitCart->setLandingPage('Foo');

        return [
            [
                $withoutToken,
                ApplicationContext::LANDINGPAGE_TYPE_BILLING,
            ],
            [
                $withoutTokenAndId,
                ApplicationContext::LANDINGPAGE_TYPE_LOGIN,
            ],
            [
                $submitCart,
                ApplicationContext::LANDINGPAGE_TYPE_LOGIN,
            ],
        ];
    }

    public function testGetPaymentWithOrderNumber(): void
    {
        $orderNumberPrefix = 'TEST_';

        $settings = $this->createDefaultSettingStruct();
        $settings->setSendOrderNumber(true);
        $settings->setOrderNumberPrefix($orderNumberPrefix);

        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        static::assertSame(
            $orderNumberPrefix . self::TEST_ORDER_NUMBER,
            $payment['transactions'][0]['invoice_number']
        );
    }

    public function testGetPaymentWithOrderNumberWithoutPrefix(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSendOrderNumber(true);

        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);
        if ($payment === false) {
            return;
        }

        $payment = json_decode($payment, true);

        static::assertSame(self::TEST_ORDER_NUMBER, $payment['transactions'][0]['invoice_number']);
    }

    private function createContextWithoutSalesChannel(): Context
    {
        $defaultContext = Context::createDefaultContext();
        $sourceContext = new SalesChannelApiSource('foo');

        return new Context(
            $sourceContext,
            $defaultContext->getRuleIds(),
            $defaultContext->getCurrencyId(),
            $defaultContext->getLanguageIdChain(),
            $defaultContext->getVersionId(),
            $defaultContext->getCurrencyFactor()
        );
    }
}

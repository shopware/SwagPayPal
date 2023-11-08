<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PaymentsApi\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\CurrencyRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Webhook\WebhookServiceTest;

/**
 * @internal
 */
#[Package('checkout')]
class OrderPaymentBuilderTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;

    public const TEST_ORDER_NUMBER = 'TEST_12345_TEST';
    public const TEST_ORDER_NUMBER_WITHOUT_PREFIX = '12345';
    public const TEST_ORDER_NUMBER_PREFIX = 'TEST_';
    public const TEST_ORDER_NUMBER_SUFFIX = '_TEST';
    public const TEST_ORDER_ID = 'test-order-id';
    public const EXPECTED_ITEM_NAME = 'Aerodynamic Paper Ginger Vitro';
    public const EXPECTED_PRODUCT_NUMBER = '0716562764cd43389abe16faad1838b8';
    public const EXPECTED_ITEM_CURRENCY = 'EUR';
    public const EXPECTED_ITEM_TAX = 0.0;
    public const EXPECTED_ITEM_QUANTITY = 1;

    public function testGetPayment(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        $transaction = $payment->getTransactions()->first();
        $transactionJsonString = \json_encode($transaction);
        static::assertNotFalse($transactionJsonString);

        $transactionArray = \json_decode($transactionJsonString, true);

        static::assertArrayHasKey('invoice_number', $transactionArray);
        static::assertSame(self::TEST_ORDER_NUMBER, $transactionArray['invoice_number']);
    }

    public function testGetPaymentWithoutBrandName(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::BRAND_NAME => '',
        ]);
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setName(SalesChannelRepoMock::SALES_CHANNEL_NAME);

        $payment = \json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);

        $payment = \json_decode($payment, true);

        static::assertSame(SalesChannelRepoMock::SALES_CHANNEL_NAME, $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithoutBrandNameAndSalesChannel(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::BRAND_NAME => '',
        ]);
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = $this->createContextWithoutSalesChannel();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = \json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);

        $payment = \json_decode($payment, true);

        static::assertSame('', $payment['application_context']['brand_name']);
    }

    public function testGetPaymentWithItemList(): void
    {
        $transaction = $this->assertTransaction(ConstantsForTesting::VALID_ORDER_ID);

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
        static::assertSame(self::EXPECTED_ITEM_TAX, (float) $item['tax']);
    }

    public function testGetPaymentWithoutLineItems(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($transaction);
        static::assertNull($transaction->getItemList());
    }

    public function testGetPaymentLabelTooLongIsTruncated(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $orderLineItems = $paymentTransaction->getOrder()->getLineItems();
        static::assertNotNull($orderLineItems);
        $firstOrderLineItem = $orderLineItems->first();
        static::assertNotNull($firstOrderLineItem);
        $firstOrderLineItem->setLabel($productName);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $itemList = $payment->getTransactions()->first()?->getItemList();
        static::assertNotNull($itemList);

        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliqu';
        static::assertSame($expectedItemName, $itemList->getItems()->first()?->getName());
    }

    public function testGetPaymentProductNumberTooLongIsTruncated(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $orderLineItems = $paymentTransaction->getOrder()->getLineItems();
        static::assertNotNull($orderLineItems);
        $firstOrderLineItem = $orderLineItems->first();
        static::assertNotNull($firstOrderLineItem);
        $firstOrderLineItem->setPayload(['productNumber' => $productNumber]);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $itemList = $payment->getTransactions()->first()?->getItemList();
        static::assertNotNull($itemList);

        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $itemList->getItems()->first()?->getSku());
    }

    public function testGetPaymentMissingProductNumberInPayload(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $orderLineItems = $paymentTransaction->getOrder()->getLineItems();
        static::assertNotNull($orderLineItems);
        $firstOrderLineItem = $orderLineItems->first();
        static::assertNotNull($firstOrderLineItem);
        $firstOrderLineItem->setPayload(['foo' => 'bar']);
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $itemList = $payment->getTransactions()->first()?->getItemList();
        static::assertNotNull($itemList);

        static::assertNull($itemList->getItems()->first()?->getSku());
    }

    public function testGetPaymentOrderHasNoCurrency(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $order = $paymentTransaction->getOrder();
        $currency = $order->getCurrency();
        static::assertNotNull($currency);
        $order->setCurrencyId($currency->getId());
        $order->assign(['currency' => null]);
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        static::assertSame(self::TEST_ORDER_NUMBER, $payment->getTransactions()->first()?->getInvoiceNumber());
    }

    public function testGetPaymentOrderHasNoCurrencyAndInvalidCurrencyId(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $paymentTransaction->getOrder()->setCurrencyId(CurrencyRepoMock::INVALID_CURRENCY_ID);
        $paymentTransaction->getOrder()->assign(['currency' => null]);
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Currency entity with id "%s" not found', CurrencyRepoMock::INVALID_CURRENCY_ID));
        $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
    }

    /**
     * @dataProvider dataProviderTestApplicationContext
     */
    public function testApplicationContext(SystemConfigService $settings, string $expectedResult): void
    {
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext(new SalesChannelApiSource(TestDefaults::SALES_CHANNEL));
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
        $paymentJsonString = \json_encode($payment);
        static::assertNotFalse($paymentJsonString);

        $applicationContext = \json_decode($paymentJsonString, true)['application_context'];
        static::assertSame($expectedResult, $applicationContext['landing_page']);
        static::assertSame('en-GB', $applicationContext['locale']);
        static::assertSame('commit', $applicationContext['user_action']);
    }

    public function dataProviderTestApplicationContext(): array
    {
        $withoutToken = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_ID,
            Settings::LANDING_PAGE => ApplicationContext::LANDING_PAGE_TYPE_BILLING,
        ]);

        $withoutTokenAndId = $this->createDefaultSystemConfig([
            Settings::LANDING_PAGE => ApplicationContext::LANDING_PAGE_TYPE_LOGIN,
        ]);

        $submitCart = $this->createDefaultSystemConfig([
            Settings::LANDING_PAGE => 'Foo',
        ]);

        return [
            [
                $withoutToken,
                ApplicationContext::LANDING_PAGE_TYPE_BILLING,
            ],
            [
                $withoutTokenAndId,
                ApplicationContext::LANDING_PAGE_TYPE_LOGIN,
            ],
            [
                $submitCart,
                ApplicationContext::LANDING_PAGE_TYPE_LOGIN,
            ],
        ];
    }

    public function testGetPaymentWithOrderNumber(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::SEND_ORDER_NUMBER => true,
        ]);

        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = \json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);

        $payment = \json_decode($payment, true);

        static::assertSame(self::TEST_ORDER_NUMBER, $payment['transactions'][0]['invoice_number']);
    }

    public function testGetPaymentWithOrderNumberWithoutPrefixOrSuffix(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::SEND_ORDER_NUMBER => true,
            Settings::ORDER_NUMBER_PREFIX => '',
            Settings::ORDER_NUMBER_SUFFIX => '',
        ]);

        $paymentBuilder = $this->createPaymentBuilder($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        $payment = \json_encode($paymentBuilder->getPayment($paymentTransaction, $salesChannelContext));
        static::assertNotFalse($payment);

        $payment = \json_decode($payment, true);

        static::assertSame(self::TEST_ORDER_NUMBER_WITHOUT_PREFIX, $payment['transactions'][0]['invoice_number']);
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

    private function assertTransaction(string $orderId): array
    {
        $settings = $this->createDefaultSystemConfig([Settings::LANDING_PAGE => 'Foo']);
        $paymentBuilder = $this->createPaymentBuilder($settings);

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $paymentTransaction = $this->createPaymentTransactionStruct($orderId);

        $payment = $paymentBuilder->getPayment($paymentTransaction, $salesChannelContext);

        $transaction = \json_encode($payment->getTransactions()->first());

        static::assertNotFalse($transaction);

        return \json_decode($transaction, true);
    }
}

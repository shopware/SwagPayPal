<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class OrderFromCartBuilderTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    public function testGetOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->createOrderFromCartBuilder()->getOrder($this->createCart('', false), new Request(), $salesChannelContext, null);
    }

    public function testGetOrderInvalidIntent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $this->createOrderFromCartBuilder([Settings::INTENT => 'invalidIntent'])->getOrder($this->createCart('', false), new Request(), $salesChannelContext, null);
    }

    public function testGetOrderInvalidLandingPageType(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "landingPage" is missing or invalid');
        $this->createOrderFromCartBuilder([Settings::LANDING_PAGE => 'invalidLandingPageType'])->getOrder($this->createCart(''), new Request(), $salesChannelContext, null);
    }

    public function testGetOrderWithItemWithoutPrice(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $cart = $this->createCart('', true, 0.0, 0.0);
        $lineItem = $this->createLineItem(null);
        $lineItem->setPrice(null);
        $cart->add($lineItem);

        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);

        static::assertSame([], $order->getPurchaseUnits()->first()?->getItems()?->getElements());
    }

    public function testGetOrderWithDisabledSubmitCartConfig(): void
    {
        $cart = $this->createCart('');
        $salesChannelContext = $this->createSalesChannelContext();

        $order = $this->createOrderFromCartBuilder([Settings::SUBMIT_CART => false])->getOrder($cart, new Request(), $salesChannelContext, null);
        $purchaseUnit = $order->getPurchaseUnits()->first();
        static::assertNotNull($purchaseUnit);
        static::assertNull($purchaseUnit->getAmount()->getBreakdown());
    }

    public function testGetOrderWithMismatchingAmount(): void
    {
        $cart = $this->createCartWithLineItem(new CalculatedPrice(5.0, 5.95, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $salesChannelContext = $this->createSalesChannelContext();

        $order = $this->createOrderFromCartBuilder([Settings::SUBMIT_CART => false])->getOrder($cart, new Request(), $salesChannelContext, null);
        $purchaseUnit = $order->getPurchaseUnits()->first();
        static::assertNotNull($purchaseUnit);
        static::assertNull($purchaseUnit->getAmount()->getBreakdown());
        static::assertNull($purchaseUnit->getItems());
    }

    public function testGetOrderWithProductWithZeroPrice(): void
    {
        $cart = $this->createCartWithLineItem(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $cart->setPrice($this->createCartPrice(0.0, 0.0, 0.0));
        $salesChannelContext = $this->createSalesChannelContext();
        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);

        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame('0.00', $paypalOrderItems[0]->getUnitAmount()->getValue());
    }

    public function testGetOrderWithNegativePriceLineItemHasCorrectItemArray(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 9.84, 9.84);
        $discount = new CalculatedPrice(-2.5, -2.5, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $cart->add($this->createLineItem($discount, LineItem::PROMOTION_LINE_ITEM_TYPE));
        $cart->add($this->createLineItem($productPrice));

        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);

        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame(0, \array_keys($paypalOrderItems)[0], 'First array key of the PayPal items array must be 0.');
    }

    public function testLineItemLabelTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 12.34, 12.34);
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $cartLineItem = $this->createLineItem($productPrice);
        $cartLineItem->setLabel($productName);
        $cart->add($cartLineItem);

        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);
        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magn';
        static::assertSame($expectedItemName, $paypalOrderItems[0]->getName());
    }

    public function testLineItemProductNumberTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 12.34, 12.34);
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $cartLineItem = $this->createLineItem($productPrice);
        $cartLineItem->setPayloadValue('productNumber', $productNumber);
        $cart->add($cartLineItem);

        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);
        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $paypalOrderItems[0]->getSku());
    }

    public function testGetOrderFromNetCart(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $productNetPrice = 168.07;
        $productTax = 31.93;
        $taxRate = 19.0;

        $cart = $this->createCart('', true, $productNetPrice, $productNetPrice + $productTax);
        $cart->add($this->createLineItem(new CalculatedPrice($productNetPrice, $productNetPrice, new CalculatedTaxCollection([19 => new CalculatedTax($productTax, 19, $productNetPrice)]), new TaxRuleCollection())));
        $cartPrice = new CartPrice(
            $productNetPrice,
            $productNetPrice + $productTax,
            $productNetPrice,
            new CalculatedTaxCollection([new CalculatedTax($productTax, $taxRate, $productNetPrice)]),
            new TaxRuleCollection([new TaxRule($taxRate)]),
            CartPrice::TAX_STATE_NET
        );
        $cart->setPrice($cartPrice);
        $firstCartTransaction = $cart->getTransactions()->first();
        static::assertNotNull($firstCartTransaction);
        $firstCartTransaction->setAmount(
            new CalculatedPrice(
                $productNetPrice,
                $productNetPrice + $productTax,
                new CalculatedTaxCollection([new CalculatedTax($productTax, $taxRate, $productNetPrice)]),
                new TaxRuleCollection([new TaxRule($taxRate)])
            )
        );

        $order = $this->createOrderFromCartBuilder()->getOrder($cart, new Request(), $salesChannelContext, null);
        $breakdown = $order->getPurchaseUnits()->first()?->getAmount()->getBreakdown();
        static::assertNotNull($breakdown);
        $taxTotal = $breakdown->getTaxTotal();
        static::assertNotNull($taxTotal);

        static::assertSame((string) $productTax, $taxTotal->getValue());
    }

    private function createOrderFromCartBuilder(array $settings = []): OrderFromCartBuilder
    {
        $systemConfig = $this->createDefaultSystemConfig($settings);
        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        return new OrderFromCartBuilder(
            $priceFormatter,
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            new EventDispatcherMock(),
            new LoggerMock(),
            $this->createMock(VaultTokenService::class),
        );
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        return $salesChannelContext;
    }

    private function createCartWithLineItem(?CalculatedPrice $lineItemPrice = null): Cart
    {
        $cart = $this->createCart('', true, $lineItemPrice ? $lineItemPrice->getTotalPrice() : 9.0, $lineItemPrice ? $lineItemPrice->getTotalPrice() : 10.9);
        $cart->add($this->createLineItem($lineItemPrice));

        return $cart;
    }
}

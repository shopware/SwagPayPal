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
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\PriceFormatter;

class OrderFromCartBuilderTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    public function testGetOrderWithoutTransction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->createOrderFromCartBuilder()->getOrder($this->createCart('', false), $salesChannelContext, null);
    }

    public function testGetOrderInvalidIntent(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setIntent('invalidIntent');
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $this->createOrderFromCartBuilder($settings)->getOrder($this->createCart('', false), $salesChannelContext, null);
    }

    public function testGetOrderInvalidLandingPageType(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setLandingPage('invalidLandingPageType');
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "landingPage" is missing or invalid');
        $this->createOrderFromCartBuilder($settings)->getOrder($this->createCart(''), $salesChannelContext, null);
    }

    public function testGetOrderWithItemWithoutPrice(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $salesChannelContext = $this->createSalesChannelContext();
        $cart = $this->createCartWithLineItem();
        $order = $this->createOrderFromCartBuilder($settings)->getOrder($cart, $salesChannelContext, null);

        static::assertSame([], $order->getPurchaseUnits()[0]->getItems());
    }

    public function testGetOrderWithDisabledSubmitCartConfig(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSubmitCart(false);
        $cart = $this->createCart('');
        $salesChannelContext = $this->createSalesChannelContext();

        $order = $this->createOrderFromCartBuilder($settings)->getOrder($cart, $salesChannelContext, null);
        static::assertNull($order->getPurchaseUnits()[0]->getAmount()->getBreakdown());
    }

    public function testGetOrderWithProductWithZeroPrice(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $cart = $this->createCartWithLineItem(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $salesChannelContext = $this->createSalesChannelContext();
        $order = $this->createOrderFromCartBuilder($settings)->getOrder($cart, $salesChannelContext, null);

        $paypalOrderItems = $order->getPurchaseUnits()[0]->getItems();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame('0.00', $paypalOrderItems[0]->getUnitAmount()->getValue());
    }

    public function testGetOrderWithNegativePriceLineItemHasCorrectItemArray(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('');
        $discount = new CalculatedPrice(-2.5, -2.5, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $cart->add($this->createLineItem($discount, LineItem::PROMOTION_LINE_ITEM_TYPE));
        $cart->add($this->createLineItem($productPrice));

        $order = $this->createOrderFromCartBuilder($settings)->getOrder($cart, $salesChannelContext, null);

        $paypalOrderItems = $order->getPurchaseUnits()[0]->getItems();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame(0, \array_keys($paypalOrderItems)[0], 'First array key of the PayPal items array must be 0.');
    }

    private function createOrderFromCartBuilder(?SwagPayPalSettingStruct $settings = null): OrderFromCartBuilder
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($settings);
        $priceFormatter = new PriceFormatter();

        return new OrderFromCartBuilder(
            $settingsService,
            $priceFormatter,
            new AmountProvider($priceFormatter)
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
        $cart = $this->createCart('');
        $cart->add($this->createLineItem($lineItemPrice));

        return $cart;
    }

    private function createLineItem(
        ?CalculatedPrice $lineItemPrice,
        string $lineItemType = LineItem::PRODUCT_LINE_ITEM_TYPE
    ): LineItem {
        $lineItem = new LineItem(Uuid::randomHex(), $lineItemType);
        if ($lineItemPrice !== null) {
            $lineItem->setPrice($lineItemPrice);
        }

        return $lineItem;
    }
}

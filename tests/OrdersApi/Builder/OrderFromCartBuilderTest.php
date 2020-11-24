<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Context;
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
        $cart = $this->createCart('');
        $cart->setLineItems(new LineItemCollection([new LineItem('line-item-id', LineItem::PRODUCT_LINE_ITEM_TYPE)]));
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
}

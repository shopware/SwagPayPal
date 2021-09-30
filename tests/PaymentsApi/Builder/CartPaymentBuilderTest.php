<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PaymentsApi\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Swag\PayPal\PaymentsApi\Builder\CartPaymentBuilder;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;

class CartPaymentBuilderTest extends TestCase
{
    use ServicesTrait;
    use CartTrait;
    use SalesChannelContextTrait;

    public function testGetPaymentLineItemMissingPrice(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $cart = $this->createCart('');
        $product = $this->createLineItem(null);
        $product->setPrice(null);
        $cart->add($product);

        $payment = $this->createCartPaymentBuilder()->getPayment($cart, $salesChannelContext, '', true);
        static::assertNull($payment->getTransactions()[0]->getItemList());
    }

    public function testGetPaymentLabelTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $cart = $this->createCart('');
        $productPrice = new CalculatedPrice(10.90, 10.90, new CalculatedTaxCollection(), new TaxRuleCollection());
        $product = $this->createLineItem($productPrice);
        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $product->setLabel($productName);
        $cart->add($product);

        $payment = $this->createCartPaymentBuilder()->getPayment($cart, $salesChannelContext, '', true);
        $itemList = $payment->getTransactions()[0]->getItemList();
        static::assertNotNull($itemList);

        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliqu';
        static::assertSame($expectedItemName, $itemList->getItems()[0]->getName());
    }

    public function testGetPaymentProductNumberTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $cart = $this->createCart('');
        $productPrice = new CalculatedPrice(10.90, 10.90, new CalculatedTaxCollection(), new TaxRuleCollection());
        $product = $this->createLineItem($productPrice);
        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $product->setPayloadValue('productNumber', $productNumber);
        $cart->add($product);

        $payment = $this->createCartPaymentBuilder()->getPayment($cart, $salesChannelContext, '', true);
        $itemList = $payment->getTransactions()[0]->getItemList();
        static::assertNotNull($itemList);

        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $itemList->getItems()[0]->getSku());
    }

    public function testGetPaymentWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $cart = $this->createCart('');
        $cart->setTransactions(new TransactionCollection());

        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->createCartPaymentBuilder()->getPayment($cart, $salesChannelContext, '');
    }

    private function createCartPaymentBuilder(): CartPaymentBuilder
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
        ]);
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);

        return new CartPaymentBuilder(
            $localeCodeProvider,
            new PriceFormatter(),
            new EventDispatcherMock(),
            new LoggerMock(),
            $settings
        );
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Framework\Uuid\Uuid;

trait CartTrait
{
    protected function createCart(string $paypalPaymentMethodId, bool $withTransaction = true, float $netPrice = 9.0, float $totalPrice = 10.9): Cart
    {
        $cart = new Cart('test-cart', Uuid::randomHex());
        if ($withTransaction) {
            $transaction = new Transaction(
                new CalculatedPrice(
                    $totalPrice,
                    $totalPrice,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ),
                $paypalPaymentMethodId
            );
            $cart->setTransactions(new TransactionCollection([$transaction]));
        }

        $cart->setPrice($this->createCartPrice($netPrice, $totalPrice, $netPrice));

        return $cart;
    }

    protected function createCartPrice(float $netPrice, float $totalPrice, float $positionPrice): CartPrice
    {
        return new CartPrice(
            $netPrice,
            $totalPrice,
            $positionPrice,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );
    }

    protected function createLineItem(
        ?CalculatedPrice $lineItemPrice,
        string $lineItemType = LineItem::PRODUCT_LINE_ITEM_TYPE
    ): LineItem {
        $lineItem = new LineItem(Uuid::randomHex(), $lineItemType);
        if ($lineItemPrice !== null) {
            $lineItem->setPrice($lineItemPrice);
        } else {
            $lineItem->setPrice(new CalculatedPrice(10.9, 10.9, new CalculatedTaxCollection(), new TaxRuleCollection()));
        }

        return $lineItem;
    }
}

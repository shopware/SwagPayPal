<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCheckoutButtonDataTest extends TestCase
{
    public function testExpressCheckoutButtonDataStruct(): void
    {
        $buttonData = (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => true,
            'offCanvasEnabled' => true,
            'loginEnabled' => true,
            'cartEnabled' => true,
            'listingEnabled' => true,
            'buttonColor' => 'blue',
            'buttonShape' => 'pill',
            'clientId' => 'testClientId',
            'languageIso' => 'en_GB',
            'currency' => 'EUR',
            'intent' => 'sale',
            'addProductToCart' => false,
        ]);

        static::assertTrue($buttonData->getProductDetailEnabled());
        static::assertTrue($buttonData->getOffCanvasEnabled());
        static::assertTrue($buttonData->getLoginEnabled());
        static::assertTrue($buttonData->getCartEnabled());
        static::assertTrue($buttonData->getListingEnabled());
        static::assertSame('blue', $buttonData->getButtonColor());
        static::assertSame('pill', $buttonData->getButtonShape());
        static::assertSame('testClientId', $buttonData->getClientId());
        static::assertSame('en_GB', $buttonData->getLanguageIso());
        static::assertSame('EUR', $buttonData->getCurrency());
        static::assertSame('sale', $buttonData->getIntent());
        static::assertFalse($buttonData->getAddProductToCart());
    }
}

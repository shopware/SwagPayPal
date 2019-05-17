<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;

class ExpressCheckoutButtonDataTest extends TestCase
{
    public function testExpressCheckoutButtonDataStruct(): void
    {
        $buttonData = (new ExpressCheckoutButtonData())->assign([
            'offCanvasEnabled' => true,
            'loginEnabled' => true,
            'cartEnabled' => true,
            'useSandbox' => false,
            'buttonColor' => 'blue',
            'buttonShape' => 'pill',
            'clientId' => 'testClientId',
            'languageIso' => 'en_GB',
            'currency' => 'EUR',
            'intent' => 'sale',
        ]);

        static::assertTrue($buttonData->getOffCanvasEnabled());
        static::assertTrue($buttonData->getLoginEnabled());
        static::assertTrue($buttonData->getCartEnabled());
        static::assertFalse($buttonData->getUseSandbox());
        static::assertSame('blue', $buttonData->getButtonColor());
        static::assertSame('pill', $buttonData->getButtonShape());
        static::assertSame('testClientId', $buttonData->getClientId());
        static::assertSame('en_GB', $buttonData->getLanguageIso());
        static::assertSame('EUR', $buttonData->getCurrency());
        static::assertSame('sale', $buttonData->getIntent());
    }
}

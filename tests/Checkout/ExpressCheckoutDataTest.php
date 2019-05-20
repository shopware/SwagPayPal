<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;

class ExpressCheckoutDataTest extends TestCase
{
    public function testExpressCheckoutDataStruct(): void
    {
        $expressCheckoutData = new ExpressCheckoutData();

        $expressCheckoutData->setPaymentMethodId('testPayPalPaymentMethodId');
        $expressCheckoutData->setPayerId('testPayerId');
        $expressCheckoutData->setIsExpressCheckout(false);
        $expressCheckoutData->setPaymentId('testTransactionId');

        static::assertSame('testPayPalPaymentMethodId', $expressCheckoutData->getPaymentMethodId());
        static::assertSame('testPayerId', $expressCheckoutData->getPayerId());
        static::assertFalse($expressCheckoutData->isExpressCheckout());
        static::assertSame('testTransactionId', $expressCheckoutData->getPaymentId());
    }
}

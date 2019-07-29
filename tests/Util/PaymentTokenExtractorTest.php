<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\Util\PaymentTokenExtractor;

class PaymentTokenExtractorTest extends TestCase
{
    public function testExtract(): void
    {
        $payment = new Payment();
        $payment->assign([
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-44X706219E3526258',
                    'rel' => 'approval_url',
                    'method' => 'REDIRECT',
                ],
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q/execute',
                    'rel' => 'execute',
                    'method' => 'POST',
                ],
            ],
        ]);

        $token = PaymentTokenExtractor::extract($payment);
        static::assertSame('EC-44X706219E3526258', $token);
    }

    public function testExtractWithoutApprovalUrl(): void
    {
        $payment = new Payment();
        $payment->assign([
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q/execute',
                    'rel' => 'execute',
                    'method' => 'POST',
                ],
            ],
        ]);

        $token = PaymentTokenExtractor::extract($payment);
        static::assertSame('', $token);
    }

    public function testExtractWithInvalidApprovalUrl(): void
    {
        $payment = new Payment();
        $payment->assign([
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout',
                    'rel' => 'approval_url',
                    'method' => 'REDIRECT',
                ],
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3A3234483P2338009KTTFX7Q/execute',
                    'rel' => 'execute',
                    'method' => 'POST',
                ],
            ],
        ]);

        $token = PaymentTokenExtractor::extract($payment);
        static::assertSame('', $token);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateResponseFixture;
use Swag\PayPal\Util\PaymentTokenExtractor;

/**
 * @internal
 */
#[Package('checkout')]
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
                    'href' => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . CreateResponseFixture::CREATE_PAYMENT_APPROVAL_TOKEN,
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
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_TOKEN, $token);
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

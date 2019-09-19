<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Api;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\Api\Payment;

class PaymentTest extends TestCase
{
    public function testJsonSerializable(): void
    {
        $payment = new Payment();
        $payment->assign([
            'redirect_urls' => ['return_url' => 'return'],
        ]);

        static::assertSame(
            '{"id":null,"intent":null,"state":null,"cart":null,"payer":null,"transactions":null,"create_time":null,"update_time":null,"links":null,"redirect_urls":{"return_url":"return","cancel_url":null},"application_context":null}',
            json_encode($payment)
        );
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

/**
 * @internal
 */
class CaptureOrdersResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => '3VW255679D905545W',
            'create_time' => '2019-03-08T13:45:10Z',
            'update_time' => '2019-03-08T13:45:20Z',
            'amount' => [
                'total' => '21.85',
                'currency' => 'EUR',
            ],
            'is_final_capture' => true,
            'state' => 'completed',
            'reason_code' => 'None',
            'parent_payment' => 'PAY-6WS55891D73472102LSBHCOA',
            'transaction_fee' => [
                'value' => '0.77',
                'currency' => 'EUR',
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/capture/3VW255679D905545W',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/capture/3VW255679D905545W/refund',
                    'rel' => 'refund',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-5CY96733W27066031',
                    'rel' => 'order',
                    'method' => 'GET',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-6WS55891D73472102LSBHCOA',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

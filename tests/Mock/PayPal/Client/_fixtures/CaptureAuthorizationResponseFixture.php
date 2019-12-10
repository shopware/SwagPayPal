<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class CaptureAuthorizationResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => '73V284073V954772P',
            'create_time' => '2019-03-07T15:59:33Z',
            'update_time' => '2019-03-07T15:59:33Z',
            'amount' => [
                'total' => '22.85',
                'currency' => 'EUR',
            ],
            'is_final_capture' => true,
            'state' => 'completed',
            'reason_code' => 'None',
            'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
            'transaction_fee' => [
                'value' => '0.78',
                'currency' => 'EUR',
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/capture/73V284073V954772P',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/capture/73V284073V954772P/refund',
                    'rel' => 'refund',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/86616545JW445624A',
                    'rel' => 'authorization',
                    'method' => 'GET',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LSAT63Q0NM35123M7232615L',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

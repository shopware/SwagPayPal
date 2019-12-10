<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class RefundCaptureResponseFixture
{
    public const REFUND_AMOUNT = '1.00';

    public static function get(): array
    {
        return [
            'id' => '53Y31239X18368210',
            'create_time' => '2019-03-07T15:59:33Z',
            'update_time' => '2019-03-08T09:05:23Z',
            'state' => 'completed',
            'amount' => [
                'total' => self::REFUND_AMOUNT,
                'currency' => 'EUR',
            ],
            'refund_from_transaction_fee' => [
                'currency' => 'EUR',
                'value' => '0.01',
            ],
            'total_refunded_amount' => [
                'currency' => 'EUR',
                'value' => '1.00',
            ],
            'refund_from_received_amount' => [
                'currency' => 'EUR',
                'value' => '0.99',
            ],
            'capture_id' => '73V284073V954772P',
            'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/53Y31239X18368210',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LSAT63Q0NM35123M7232615L',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/capture/73V284073V954772P',
                    'rel' => 'capture',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

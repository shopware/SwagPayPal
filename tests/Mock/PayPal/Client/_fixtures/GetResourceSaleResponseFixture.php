<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class GetResourceSaleResponseFixture
{
    public const ID = '7G096060K6661313W';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'state' => 'completed',
            'amount' => [
                'total' => '230.00',
                'currency' => 'EUR',
                'details' => [
                    'subtotal' => '193.28',
                    'tax' => '36.72',
                    'shipping' => '3.90',
                    'insurance' => '0.00',
                    'handling_fee' => '0.00',
                    'shipping_discount' => '0.00',
                ],
            ],
            'payment_mode' => 'INSTANT_TRANSFER',
            'protection_eligibility' => 'ELIGIBLE',
            'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
            'transaction_fee' => [
                'value' => '4.72',
                'currency' => 'EUR',
            ],
            'parent_payment' => 'PAY-3NJ117295B240983LLQDISII',
            'create_time' => '2018-12-04T14:06:19Z',
            'update_time' => '2018-12-04T14:06:19Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/7G096060K6661313W',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/7G096060K6661313W/refund',
                    'rel' => 'refund',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3NJ117295B240983LLQDISII',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

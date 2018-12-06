<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Client\_fixtures;

class GetPaymentSaleResponseFixture
{
    public const TRANSACTION_AMOUNT_DETAILS_SUBTOTAL = '193.28';

    public static function get(): array
    {
        return [
            'id' => 'PAY-3NJ117295B240983LLQDISII',
            'intent' => 'sale',
            'state' => 'approved',
            'cart' => '27499824YX3936050',
            'payer' => [
                'payment_method' => 'paypal',
                'status' => 'VERIFIED',
                'payer_info' => [
                    'email' => 'test@shopware.com',
                    'first_name' => 'Test',
                    'last_name' => 'Test',
                    'payer_id' => 'BNJDKJVFBCXPJ',
                    'shipping_address' => [
                        'recipient_name' => 'Test Test',
                        'line1' => 'Wienerstraß1',
                        'line2' => 'Adresszusatz',
                        'city' => 'Wien',
                        'state' => '',
                        'postal_code' => '1234',
                        'country_code' => 'AT',
                    ],
                    'phone' => '7884987824',
                    'country_code' => 'DE',
                ],
            ],
            'transactions' => [
                0 => [
                    'amount' => [
                        'total' => '230.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => self::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
                            'tax' => '36.72',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'HCKBUJL8YWQZS',
                    ],
                    'item_list' => [
                        'items' => [],
                        'shipping_address' => [
                            'recipient_name' => 'Test Test',
                            'line1' => 'Wienerstraß1',
                            'line2' => 'Adresszusatz',
                            'city' => 'Wien',
                            'state' => '',
                            'postal_code' => '1234',
                            'country_code' => 'AT',
                        ],
                    ],
                    'related_resources' => [
                        0 => [
                            'sale' => [
                                'id' => '7G096060K6661313W',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '230.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '193.28',
                                        'tax' => '36.72',
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
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2018-12-04T14:03:13Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-3NJ117295B240983LLQDISII',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

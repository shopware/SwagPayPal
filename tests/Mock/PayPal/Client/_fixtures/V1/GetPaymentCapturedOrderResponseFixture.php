<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class GetPaymentCapturedOrderResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => 'PAY-6WS55891D73472102LSBHCOA',
            'intent' => 'order',
            'state' => 'approved',
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
                        'line1' => 'Ebbinghoff 10',
                        'city' => 'Schöppingen',
                        'state' => '',
                        'postal_code' => '48624',
                        'country_code' => 'DE',
                    ],
                    'phone' => '7884987824',
                    'country_code' => 'DE',
                ],
            ],
            'transactions' => [
                0 => [
                    'amount' => [
                        'total' => '21.85',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => '17.95',
                            'shipping' => '3.90',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'HCKBUJL8YWQZS',
                    ],
                    'item_list' => [
                        'items' => [],
                        'shipping_address' => [
                            'recipient_name' => 'Test Test',
                            'line1' => 'Ebbinghoff 10',
                            'city' => 'Schöppingen',
                            'state' => '',
                            'postal_code' => '48624',
                            'country_code' => 'DE',
                        ],
                    ],
                    'related_resources' => [
                        0 => [
                            'order' => [
                                'id' => 'O-5CY96733W27066031',
                                'create_time' => '2019-03-13T14:54:08Z',
                                'update_time' => '2019-03-13T14:54:08Z',
                                'amount' => [
                                    'total' => '21.85',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '17.95',
                                        'shipping' => '3.90',
                                    ],
                                ],
                                'state' => 'COMPLETED',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-5CY96733W27066031',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-6WS55891D73472102LSBHCOA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-5CY96733W27066031/do-void',
                                        'rel' => 'void',
                                        'method' => 'POST',
                                    ],
                                    3 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-5CY96733W27066031/authorize',
                                        'rel' => 'authorization',
                                        'method' => 'POST',
                                    ],
                                    4 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-5CY96733W27066031/capture',
                                        'rel' => 'capture',
                                        'method' => 'POST',
                                    ],
                                ],
                                'parent_payment' => 'PAY-6WS55891D73472102LSBHCOA',
                            ],
                        ],
                        1 => [
                            'capture' => [
                                'id' => '3VW255679D905545W',
                                'amount' => [
                                    'total' => '21.85',
                                    'currency' => 'EUR',
                                ],
                                'state' => 'completed',
                                'transaction_fee' => [
                                    'value' => '0.77',
                                    'currency' => 'EUR',
                                ],
                                'invoice_number' => 'SW-1234',
                                'custom' => 'custom text',
                                'parent_payment' => 'PAY-6WS55891D73472102LSBHCOA',
                                'create_time' => '2019-03-08T13:45:19Z',
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
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-6WS55891D73472102LSBHCOA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2019-03-08T13:42:16Z',
            'update_time' => '2019-03-08T13:45:19Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-6WS55891D73472102LSBHCOA',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

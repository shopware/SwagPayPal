<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class ExecutePaymentOrderResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => 'PAY-35B10430TC590490WLQDJXTI',
            'intent' => 'order',
            'state' => 'approved',
            'cart' => '1KU5881047858235H',
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
                        'line2' => 'Addresszusatz',
                        'city' => 'Schöppingen',
                        'state' => '',
                        'phone' => '4084217591',
                        'postal_code' => '4862',
                        'country_code' => 'AT',
                    ],
                    'country_code' => 'DE',
                ],
            ],
            'transactions' => [
                0 => [
                    'amount' => [
                        'total' => '375.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => '315.13',
                            'tax' => '59.87',
                            'shipping' => '0.00',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'HCKBUJL8YWQZS',
                    ],
                    'item_list' => [
                        'shipping_address' => [
                            'recipient_name' => 'Test Test',
                            'line1' => 'Ebbinghoff 10',
                            'line2' => 'Addresszusatz',
                            'city' => 'Schöppingen',
                            'state' => '',
                            'phone' => '4084217591',
                            'postal_code' => '4862',
                            'country_code' => 'AT',
                        ],
                    ],
                    'related_resources' => [
                        0 => [
                            'order' => [
                                'id' => 'O-7PS41727C2382141U',
                                'create_time' => '2018-12-04T15:23:24Z',
                                'update_time' => '2018-12-04T15:23:24Z',
                                'amount' => [
                                    'total' => '375.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '315.13',
                                        'tax' => '59.87',
                                        'shipping' => '0.00',
                                    ],
                                ],
                                'state' => 'PENDING',
                                'reason_code' => 'ORDER',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-35B10430TC590490WLQDJXTI',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/do-void',
                                        'rel' => 'void',
                                        'method' => 'POST',
                                    ],
                                    3 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/authorize',
                                        'rel' => 'authorization',
                                        'method' => 'POST',
                                    ],
                                    4 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/capture',
                                        'rel' => 'capture',
                                        'method' => 'POST',
                                    ],
                                ],
                                'parent_payment' => 'PAY-35B10430TC590490WLQDJXTI',
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2018-12-04T15:23:25Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-35B10430TC590490WLQDJXTI',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

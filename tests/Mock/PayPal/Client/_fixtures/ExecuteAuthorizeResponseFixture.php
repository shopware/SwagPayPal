<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Client\_fixtures;

class ExecuteAuthorizeResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => 'PAY-2G272278LH1357142LQDIU4Q',
            'intent' => 'authorize',
            'state' => 'approved',
            'cart' => '1SU34407K73133347',
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
                        'email' => 'test@shopware.de',
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
                            'authorization' => [
                                'id' => '3TG33581TT5577908',
                                'state' => 'authorized',
                                'amount' => [
                                    'total' => '375.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '315.13',
                                        'tax' => '59.87',
                                        'shipping' => '0.00',
                                    ],
                                ],
                                'payment_mode' => 'INSTANT_TRANSFER',
                                'reason_code' => 'AUTHORIZATION',
                                'protection_eligibility' => 'ELIGIBLE',
                                'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
                                'parent_payment' => 'PAY-2G272278LH1357142LQDIU4Q',
                                'valid_until' => '2019-01-02T14:09:02Z',
                                'create_time' => '2018-12-04T14:09:02Z',
                                'update_time' => '2018-12-04T14:09:02Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/3TG33581TT5577908',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/3TG33581TT5577908/capture',
                                        'rel' => 'capture',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/3TG33581TT5577908/void',
                                        'rel' => 'void',
                                        'method' => 'POST',
                                    ],
                                    3 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/3TG33581TT5577908/reauthorize',
                                        'rel' => 'reauthorize',
                                        'method' => 'POST',
                                    ],
                                    4 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-2G272278LH1357142LQDIU4Q',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2018-12-04T14:09:03Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-2G272278LH1357142LQDIU4Q',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

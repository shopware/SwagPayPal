<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class ExecutePaymentSaleResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => 'PAY-46D69568SY211161GLP6CHGA',
            'intent' => 'sale',
            'state' => 'approved',
            'cart' => '1X136001V5949163B',
            'payer' => [
                'payment_method' => 'paypal',
                'status' => 'VERIFIED',
                'payer_info' => [
                    'email' => 'test@shopware.com',
                    'first_name' => 'Test',
                    'last_name' => 'Test',
                    'payer_id' => 'BNJDKJVFBCXPJ',
                    'phone' => '605-521-1234',
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
                        'total' => '727.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => '727.00',
                            'tax' => '0.00',
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
                        'shipping_options' => [
                            0 => null,
                        ],
                    ],
                    'related_resources' => [
                        0 => [
                            'sale' => [
                                'id' => '5GB9720606957970A',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '727.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '727.00',
                                    ],
                                ],
                                'payment_mode' => 'INSTANT_TRANSFER',
                                'protection_eligibility' => 'ELIGIBLE',
                                'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
                                'transaction_fee' => [
                                    'value' => '14.16',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAY-46D69568SY211161GLP6CHGA',
                                'create_time' => '2018-11-26T16:47:46Z',
                                'update_time' => '2018-11-26T16:47:46Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/5GB9720606957970A',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/5GB9720606957970A/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-46D69568SY211161GLP6CHGA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2018-11-26T16:47:47Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-46D69568SY211161GLP6CHGA',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

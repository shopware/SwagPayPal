<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Client\_fixtures;

class GetSaleWithRefundResponseFixture
{
    public const TRANSACTION_AMOUNT_DETAILS_SUBTOTAL = '12.35';

    public static function get(): array
    {
        return [
            'id' => 'PAY-0MX009757T271510FLQES2MA',
            'intent' => 'sale',
            'state' => 'approved',
            'cart' => '046733234W038363W',
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
                        'total' => '240.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => self::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
                            'tax' => '15.70',
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
                                'id' => '98N85671E85717541',
                                'state' => 'partially_refunded',
                                'amount' => [
                                    'total' => '240.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                            'subtotal' => '12.35',
                                            'tax' => '15.70',
                                        ],
                                ],
                                'payment_mode' => 'INSTANT_TRANSFER',
                                'protection_eligibility' => 'ELIGIBLE',
                                'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
                                'transaction_fee' => [
                                    'value' => '4.91',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAY-0MX009757T271510FLQES2MA',
                                'create_time' => '2018-12-06T14:08:08Z',
                                'update_time' => '2018-12-06T14:16:34Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/98N85671E85717541',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/98N85671E85717541/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-0MX009757T271510FLQES2MA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                        1 => [
                            'refund' => [
                                'id' => '9U298445KW082313C',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '12.35',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAY-0MX009757T271510FLQES2MA',
                                'sale_id' => '98N85671E85717541',
                                'create_time' => '2018-12-06T14:14:37Z',
                                'update_time' => '2018-12-06T14:14:37Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/9U298445KW082313C',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-0MX009757T271510FLQES2MA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/98N85671E85717541',
                                        'rel' => 'sale',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                        2 => [
                            'refund' => [
                                'id' => '9UJ167316W283554V',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '12.35',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAY-0MX009757T271510FLQES2MA',
                                'sale_id' => '98N85671E85717541',
                                'create_time' => '2018-12-06T14:16:34Z',
                                'update_time' => '2018-12-06T14:16:34Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/9UJ167316W283554V',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-0MX009757T271510FLQES2MA',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/98N85671E85717541',
                                        'rel' => 'sale',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2018-12-06T14:07:44Z',
            'update_time' => '2018-12-06T14:16:34Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-0MX009757T271510FLQES2MA',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

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
class GetPaymentAuthorizeResponseFixture
{
    public const ID = 'PAYID-LSAT63Q0NM35123M7232615L';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'intent' => 'authorize',
            'state' => 'approved',
            'cart' => '4H701720RR467622X',
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
                            'tax' => '0.00',
                            'shipping' => '3.90',
                            'insurance' => '0.00',
                            'handling_fee' => '0.00',
                            'shipping_discount' => '0.00',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'HCKBUJL8YWQZS',
                        'email' => 'test@shopware.com',
                    ],
                    'description' => 'Strandtuch "Ibiza"',
                    'item_list' => [
                        'items' => [
                            0 => [
                                'name' => 'Strandtuch "Ibiza"',
                                'sku' => 'SW10178',
                                'price' => '19.95',
                                'currency' => 'EUR',
                                'tax' => '0.00',
                                'quantity' => 1,
                            ],
                            1 => [
                                'name' => 'Warenkorbrabatt',
                                'sku' => 'SHIPPINGDISCOUNT',
                                'price' => '-2.00',
                                'currency' => 'EUR',
                                'tax' => '0.00',
                                'quantity' => 1,
                            ],
                        ],
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
                            'authorization' => GetResourceAuthorizeResponseFixture::get(),
                        ],
                        1 => [
                            'capture' => [
                                'id' => '73V284073V954772P',
                                'amount' => [
                                    'total' => '22.85',
                                    'currency' => 'EUR',
                                ],
                                'state' => 'partially_refunded',
                                'custom' => '',
                                'transaction_fee' => [
                                    'value' => '0.78',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
                                'invoice_number' => '',
                                'create_time' => '2019-03-08T09:05:23Z',
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
                            ],
                        ],
                        2 => [
                            'refund' => [
                                'id' => '53Y31239X18368210',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '1.00',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
                                'capture_id' => '73V284073V954772P',
                                'create_time' => '2019-03-12T14:44:10Z',
                                'update_time' => '2019-03-12T14:44:10Z',
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
                            ],
                        ],
                        3 => [
                            'refund' => [
                                'id' => '7BJ32584SS066792R',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '1.00',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
                                'capture_id' => '73V284073V954772P',
                                'create_time' => '2019-03-12T14:52:40Z',
                                'update_time' => '2019-03-12T14:52:40Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/7BJ32584SS066792R',
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
                            ],
                        ],
                        4 => [
                            'refund' => [
                                'id' => '95G02503R6815391D',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '1.00',
                                    'currency' => 'EUR',
                                ],
                                'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
                                'capture_id' => '73V284073V954772P',
                                'create_time' => '2019-03-12T14:54:05Z',
                                'update_time' => '2019-03-12T14:54:05Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/95G02503R6815391D',
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
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2019-03-07T15:57:34Z',
            'update_time' => '2019-03-08T09:05:23Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LSAT63Q0NM35123M7232615L',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

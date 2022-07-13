<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class GetRefundedOrderCapture
{
    public const ID = '9XG87361JT539825E';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'intent' => 'CAPTURE',
            'status' => 'COMPLETED',
            'purchase_units' => [
                0 => [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => '100.00',
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'EUR',
                                'value' => '100.00',
                            ],
                            'shipping' => [
                                'currency_code' => 'EUR',
                                'value' => '0.00',
                            ],
                        ],
                    ],
                    'payee' => [
                        'email_address' => 'merchant-de@shopware.de',
                        'merchant_id' => 'GQPRNN2APYDRS',
                        'display_data' => [
                            'brand_name' => 'Storefront',
                        ],
                    ],
                    'items' => [
                        0 => [
                            'name' => 'Test',
                            'unit_amount' => [
                                'currency_code' => 'EUR',
                                'value' => '100.00',
                            ],
                            'quantity' => '1',
                        ],
                    ],
                    'shipping' => [
                        'name' => [
                            'full_name' => 'Test Test',
                        ],
                        'address' => [
                            'address_line_1' => 'Ebbinghoff 10',
                            'admin_area_2' => 'Schöppingen',
                            'postal_code' => '48624',
                            'country_code' => 'DE',
                        ],
                    ],
                    'payments' => [
                        'captures' => [
                            0 => [
                                'id' => '8F71337376765912W',
                                'status' => 'PARTIALLY_REFUNDED',
                                'amount' => [
                                    'currency_code' => 'EUR',
                                    'value' => '100.00',
                                ],
                                'final_capture' => true,
                                'seller_protection' => [
                                    'status' => 'ELIGIBLE',
                                    'dispute_categories' => [
                                        0 => 'ITEM_NOT_RECEIVED',
                                        1 => 'UNAUTHORIZED_TRANSACTION',
                                    ],
                                ],
                                'seller_receivable_breakdown' => [
                                    'gross_amount' => [
                                        'currency_code' => 'EUR',
                                        'value' => '100.00',
                                    ],
                                    'paypal_fee' => [
                                        'currency_code' => 'EUR',
                                        'value' => '2.25',
                                    ],
                                    'net_amount' => [
                                        'currency_code' => 'EUR',
                                        'value' => '97.75',
                                    ],
                                ],
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8F71337376765912W',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8F71337376765912W/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                                        'rel' => 'up',
                                        'method' => 'GET',
                                    ],
                                ],
                                'create_time' => '2020-08-17T13:04:09Z',
                                'update_time' => '2020-08-17T14:03:55Z',
                            ],
                        ],
                        'refunds' => [
                            0 => GetRefund::get(),
                        ],
                    ],
                ],
            ],
            'payment_source' => [
                'card' => [
                    'last_digits' => '7109',
                    'brand' => 'VISA',
                    'type' => 'CREDIT',
                    'authentication_result' => [
                        'liability_shift' => 'POSSIBLE',
                        'three_d_secure' => [
                            'enrollment_status' => 'Y',
                            'authentication_status' => 'Y',
                        ],
                    ],
                ],
            ],
            'payer' => [
                'name' => [
                    'given_name' => 'Test',
                    'surname' => 'Test',
                ],
                'email_address' => 'customer-de@shopware.com',
                'payer_id' => 'XTW5U37QPXKJE',
                'address' => [
                    'address_line_1' => 'Ebbinghoff 10',
                    'admin_area_2' => 'Schöppingen',
                    'postal_code' => '48624',
                    'country_code' => 'DE',
                ],
            ],
            'create_time' => '2020-08-17T12:33:59Z',
            'update_time' => '2020-08-17T14:03:55Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

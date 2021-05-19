<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class GetOrderAuthorization
{
    public const ID = '5YK02325A2136392B';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'intent' => 'AUTHORIZE',
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
            'create_time' => '2020-08-17T12:37:20Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'update',
                    'method' => 'PATCH',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID . '/authorize',
                    'rel' => 'authorize',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class GetOrderCapture
{
    public const ID = '9XG87361JT539825B';
    public const PAYER_EMAIL_ADDRESS = 'customer-de@shopware.com';
    public const PAYER_NAME_GIVEN_NAME = 'Test given name';
    public const PAYER_NAME_SURNAME = 'Test surname';
    public const PAYER_ADDRESS_ADDRESS_LINE_1 = 'Ebbinghoff 10';
    public const PAYER_ADDRESS_ADMIN_AREA_2 = 'Schöppingen';
    public const PAYER_PHONE_NUMBER = '01234123456789';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'intent' => 'CAPTURE',
            'status' => 'APPROVED',
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
                        'email_address' => 'merchant-de@shopware.com',
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
                    'given_name' => self::PAYER_NAME_GIVEN_NAME,
                    'surname' => self::PAYER_NAME_SURNAME,
                ],
                'email_address' => self::PAYER_EMAIL_ADDRESS,
                'payer_id' => 'XTW5U37QPXKJE',
                'address' => [
                    'address_line_1' => self::PAYER_ADDRESS_ADDRESS_LINE_1,
                    'admin_area_1' => 'NY',
                    'admin_area_2' => self::PAYER_ADDRESS_ADMIN_AREA_2,
                    'postal_code' => '48624',
                    'country_code' => 'US',
                ],
                'phone' => [
                    'phone_type' => 'HOME',
                    'phone_number' => [
                        'national_number' => self::PAYER_PHONE_NUMBER,
                    ],
                ],
            ],
            'create_time' => '2020-08-17T12:33:59Z',
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
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID . '/capture',
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}

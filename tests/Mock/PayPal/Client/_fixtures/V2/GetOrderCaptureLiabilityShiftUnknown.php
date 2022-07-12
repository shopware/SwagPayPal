<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class GetOrderCaptureLiabilityShiftUnknown
{
    public const ID = '9XG87361JT539825F';
    public const PAYER_EMAIL_ADDRESS = 'customer-de@shopware.com';
    public const PAYER_NAME_GIVEN_NAME = 'Test given name';
    public const PAYER_NAME_SURNAME = 'Surname';
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
                            'full_name' => \sprintf('%s %s', self::PAYER_NAME_GIVEN_NAME, self::PAYER_NAME_SURNAME),
                        ],
                        'address' => [
                            'address_line_1' => self::PAYER_ADDRESS_ADDRESS_LINE_1,
                            'admin_area_1' => 'NY',
                            'admin_area_2' => self::PAYER_ADDRESS_ADMIN_AREA_2,
                            'postal_code' => '48624',
                            'country_code' => 'US',
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
                        'liability_shift' => 'UNKNOWN',
                        'three_d_secure' => [
                            'enrollment_status' => 'N',
                            'authentication_status' => 'Y',
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

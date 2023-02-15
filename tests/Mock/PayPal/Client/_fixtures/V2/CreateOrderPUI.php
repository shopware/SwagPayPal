<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

/**
 * @internal
 */
class CreateOrderPUI
{
    public const ID = '1SG34186SH474560P';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'PENDING_APPROVAL',
            'payment_source' => [
                'pay_upon_invoice' => [
                    'name' => [
                        'given_name' => 'Foo',
                        'surname' => 'Bar',
                    ],
                    'birth_date' => '1980-01-01',
                    'email' => 'foo.bar@shopware.com',
                    'phone' => [
                        'country_code' => '49',
                        'national_number' => '1234956789',
                    ],
                    'billing_address' => [
                        'address_line_1' => 'Ebbinghoff 10',
                        'admin_area_2' => 'Schöppingen',
                        'postal_code' => '48624',
                        'country_code' => 'DE',
                    ],
                    'experience_context' => [
                        'brand_name' => 'shopware AG',
                        'locale' => 'de-DE',
                        'shipping_preference' => 'GET_FROM_FILE',
                        'customer_service_instructions' => [
                            'Lorem ipsum',
                        ],
                    ],
                ],
            ],
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

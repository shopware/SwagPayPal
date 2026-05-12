<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class GetOrderAPM
{
    public const ID = '0RW08056D62072441';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'intent' => 'CAPTURE',
            'status' => 'APPROVED',
            'payment_source' => [
                'sofort' => [
                    'name' => 'Test User',
                    'country_code' => 'DE',
                    'bic' => 'ABNANL2A',
                    'iban_last_chars' => '5515',
                ],
            ],
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => '100.00',
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'EUR',
                                'value' => '81.00',
                            ],
                            'tax_total' => [
                                'currency_code' => 'EUR',
                                'value' => '19.00',
                            ],
                        ],
                    ],
                    'payee' => [
                        'email_address' => 'sb-z3kah11562752@business.example.com',
                        'merchant_id' => 'AXGB7LZU9PPUY',
                    ],
                    'invoice_id' => 'e4fc285a-5614-44fd-bd81-409e704fe5fb',
                    'items' => [
                        [
                            'name' => 'Mao Feng grüner Tee',
                            'unit_amount' => [
                                'currency_code' => 'EUR',
                                'value' => '81.00',
                            ],
                            'tax' => [
                                'currency_code' => 'EUR',
                                'value' => '19.00',
                            ],
                            'quantity' => '1',
                            'category' => 'PHYSICAL_GOODS',
                        ],
                    ],
                    'shipping' => [
                        'name' => [
                            'full_name' => 'Foo Bar',
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
            'create_time' => '2022-02-17T11:44:05Z',
            'links' => [
                [
                    'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s', self::ID),
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s', self::ID),
                    'rel' => 'update',
                    'method' => 'PATCH',
                ],
                [
                    'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s/capture', self::ID),
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}

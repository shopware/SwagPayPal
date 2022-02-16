<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class CaptureOrderAPM
{
    public const ID = '0RW08056D62072441';
    public const CAPTURE_ID = '5RN05737B4100661C';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'COMPLETED',
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
                    'payments' => [
                        'captures' => [
                            [
                                'id' => self::CAPTURE_ID,
                                'status' => 'COMPLETED',
                                'amount' => [
                                    'currency_code' => 'EUR',
                                    'value' => '100.00',
                                ],
                                'final_capture' => true,
                                'seller_protection' => [
                                    'status' => 'ELIGIBLE',
                                    'dispute_categories' => [
                                        'ITEM_NOT_RECEIVED',
                                        'UNAUTHORIZED_TRANSACTION',
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
                                'invoice_id' => 'e4fc285a-5614-44fd-bd81-409e704fe5fb',
                                'links' => [
                                    [
                                        'href' => \sprintf('https://api.sandbox.paypal.com/v2/payments/captures/%s', self::CAPTURE_ID),
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    [
                                        'href' => \sprintf('https://api.sandbox.paypal.com/v2/payments/captures/%s/refund', self::CAPTURE_ID),
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    [
                                        'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s', self::ID),
                                        'rel' => 'up',
                                        'method' => 'GET',
                                    ],
                                ],
                                'create_time' => '2022-02-17T12:10:15Z',
                                'update_time' => '2022-02-17T12:10:15Z',
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                [
                    'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s', self::ID),
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

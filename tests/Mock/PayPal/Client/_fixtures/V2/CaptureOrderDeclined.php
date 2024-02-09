<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

/**
 * @internal
 */
#[Package('checkout')]
class CaptureOrderDeclined
{
    public const ID = '9XG87361JT53982FF';
    public const CAPTURE_ID = '41U19903S663426FF';

    private static bool $duplicateOrderNumber = false;

    public static function isDuplicateOrderNumber(): bool
    {
        return self::$duplicateOrderNumber;
    }

    public static function setDuplicateOrderNumber(bool $duplicateOrderNumber): void
    {
        self::$duplicateOrderNumber = $duplicateOrderNumber;
    }

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'COMPLETED',
            'intent' => PaymentIntentV2::CAPTURE,
            'purchase_units' => [
                0 => [
                    'reference_id' => 'default',
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
                                'id' => self::CAPTURE_ID,
                                'status' => 'DECLINED',
                                'amount' => [
                                    'currency_code' => 'EUR',
                                    'value' => '100.00',
                                ],
                                'final_capture' => true,
                                'disbursement_mode' => 'INSTANT',
                                'seller_protection' => [
                                    'status' => 'NOT_ELIGIBLE',
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
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/41U19903S66342642',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/41U19903S66342642/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/0EX62470MW195591G',
                                        'rel' => 'up',
                                        'method' => 'GET',
                                    ],
                                ],
                                'create_time' => '2020-08-17T13:09:30Z',
                                'update_time' => '2020-08-17T13:09:30Z',
                                'processor_response' => [
                                    'avs_code' => 'G',
                                    'response_code' => '5900',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'email_address' => 'customer-de@shopware.com',
                    'account_id' => 'XTW5U37QPXKJE',
                    'account_status' => 'VERIFIED',
                    'name' => [
                        'given_name' => 'Kunde',
                        'surname' => 'Deutschland',
                    ],
                    'address' => [
                        'address_line_1' => 'Ebbinghoff 10',
                        'admin_area_2' => 'Schöppingen',
                        'postal_code' => '48624',
                        'country_code' => 'DE',
                    ],
                ],
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/0EX62470MW195591G',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

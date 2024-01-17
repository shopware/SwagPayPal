<?php

declare(strict_types=1);
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
class GetOrderPUICompleted
{
    public const ID = '2RN82335LC179242Y';
    public const CAPTURE_ID = '03060900SU539840T';
    public const BANK_IBAN = 'DE12345678901234567890';

    public static function get(): array
    {
        return [
            'id' => CreateOrderPUI::ID,
            'intent' => 'CAPTURE',
            'status' => 'COMPLETED',
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
                    'payment_reference' => 'GD468758295',
                    'deposit_bank_details' => [
                        'bic' => 'BELADEBEXXX',
                        'bank_name' => 'Test Sparkasse - Berlin',
                        'iban' => self::BANK_IBAN,
                        'account_holder_name' => 'Paypal - Ratepay GmbH - Test Bank Account',
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
            'processing_instruction' => 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL',
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
                    'invoice_id' => '53968cf0-8122-4c05-9b86-6123cc3fbaed',
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
                            'tax_rate' => '19.00',
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
                                'invoice_id' => '53968cf0-8122-4c05-9b86-6123cc3fbaed',
                                'links' => [
                                    [
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/' . self::CAPTURE_ID,
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    [
                                        'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/' . self::CAPTURE_ID . '/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    [
                                        'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                                        'rel' => 'up',
                                        'method' => 'GET',
                                    ],
                                ],
                                'create_time' => '2022-01-25T18:13:04Z',
                                'update_time' => '2022-01-25T18:13:04Z',
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2022-01-25T18:12:56Z',
            'update_time' => '2022-01-25T18:13:04Z',
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

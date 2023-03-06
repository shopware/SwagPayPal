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
class ExecutePuiResponseFixture
{
    public static function get(): array
    {
        return [
            'id' => 'PAYID-LUTTIRY1BG54683YB519540K',
            'intent' => 'sale',
            'state' => 'approved',
            'cart' => '88817812AG3102042',
            'payer' => [
                'payment_method' => 'paypal',
                'status' => 'UNVERIFIED',
                'payer_info' => [
                    'email' => 'te@shopware.com',
                    'first_name' => 'Paul',
                    'last_name' => 'Positiv',
                    'payer_id' => 'XAA3AF5Q567EU',
                    'shipping_address' => [
                        'recipient_name' => 'Paul Positiv',
                        'line1' => 'Märchenstraße 15a',
                        'city' => 'Osnabrück',
                        'state' => '',
                        'postal_code' => '49084',
                        'country_code' => 'DE',
                    ],
                    'country_code' => 'DE',
                    'billing_address' => [
                        'line1' => 'Märchenstraße 15a',
                        'line2' => '',
                        'city' => 'Osnabrück',
                        'state' => '',
                        'postal_code' => '49084',
                        'country_code' => 'DE',
                    ],
                ],
                'external_selected_funding_instrument_type' => 'PAY_UPON_INVOICE',
            ],
            'transactions' => [
                0 => [
                    'amount' => [
                        'total' => '936.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => '936.00',
                            'tax' => '0.00',
                            'shipping' => '0.00',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'HCKBUJL8YWQZS',
                        'email' => 'info@shopware.de',
                    ],
                    'item_list' => [
                        'shipping_address' => [
                            'recipient_name' => 'Paul Positiv',
                            'line1' => 'Märchenstraße 15a',
                            'city' => 'Osnabrück',
                            'state' => '',
                            'postal_code' => '49084',
                            'country_code' => 'DE',
                        ],
                        'shipping_options' => [
                            0 => null,
                        ],
                        'shipping_phone_number' => '+497888411531',
                    ],
                    'related_resources' => [
                        0 => [
                            'sale' => [
                                'id' => '5W715903K9293994R',
                                'state' => 'completed',
                                'amount' => [
                                    'total' => '936.00',
                                    'currency' => 'EUR',
                                    'details' => [
                                        'subtotal' => '936.00',
                                    ],
                                ],
                                'payment_mode' => 'INSTANT_TRANSFER',
                                'protection_eligibility' => 'ELIGIBLE',
                                'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
                                'transaction_fee' => [
                                    'value' => '18.13',
                                    'currency' => 'EUR',
                                ],
                                'receipt_id' => '3316134136001203',
                                'parent_payment' => 'PAYID-LUTTIRY1BG54683YB519540K',
                                'create_time' => '2019-07-11T13:07:12Z',
                                'update_time' => '2019-07-11T13:07:12Z',
                                'links' => [
                                    0 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/5W715903K9293994R',
                                        'rel' => 'self',
                                        'method' => 'GET',
                                    ],
                                    1 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/5W715903K9293994R/refund',
                                        'rel' => 'refund',
                                        'method' => 'POST',
                                    ],
                                    2 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LUTTIRY1BG54683YB519540K',
                                        'rel' => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    3 => [
                                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LUTTIRY1BG54683YB519540K/payment-instruction',
                                        'rel' => 'payment_instruction',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2019-07-11T13:08:10Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LUTTIRY1BG54683YB519540K',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
            'payment_instruction' => [
                'reference_number' => '5W715903K9293994R',
                'instruction_type' => 'PAY_UPON_INVOICE',
                'recipient_banking_instruction' => [
                    'bank_name' => 'Deutsche Bank',
                    'account_holder_name' => 'PayPal Europe',
                    'international_bank_account_number' => 'DE29120700888000172131',
                    'bank_identifier_code' => 'DEUTDEDBPAL',
                ],
                'amount' => [
                    'value' => '936.00',
                    'currency' => 'EUR',
                ],
                'payment_due_date' => '2019-08-10',
                'links' => [
                    0 => [
                        'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LUTTIRY1BG54683YB519540K/payment-instruction',
                        'rel' => 'self',
                        'method' => 'GET',
                    ],
                ],
            ],
        ];
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class GetDispute
{
    public const ID = 'PP-D-33305';

    public static function get(): array
    {
        return [
            'dispute_id' => self::ID,
            'create_time' => '2020-12-01T14:32:23.000Z',
            'update_time' => '2020-12-01T14:33:01.000Z',
            'disputed_transactions' => [
                0 => [
                    'buyer_transaction_id' => '46N77154VN1593211',
                    'seller_transaction_id' => '4BE79548VW224290D',
                    'create_time' => '2020-11-24T16:09:47.000Z',
                    'transaction_status' => 'CANCELLED',
                    'gross_amount' => [
                        'currency_code' => 'GBP',
                        'value' => '20.00',
                    ],
                    'invoice_number' => '10037',
                    'custom' => 'dcb10a56959b481b8f75818e3d8140ea',
                    'buyer' => [
                        'name' => 'Max Mustermann',
                    ],
                    'seller' => [
                        'email' => 'test@example.com',
                        'merchant_id' => 'GQPRNN2APYDRS',
                        'name' => 'Test Store',
                    ],
                    'items' => [
                    ],
                    'seller_protection_eligible' => true,
                ],
            ],
            'reason' => 'INCORRECT_AMOUNT',
            'status' => 'RESOLVED',
            'dispute_amount' => [
                'currency_code' => 'GBP',
                'value' => '8.00',
            ],
            'dispute_outcome' => [
                'outcome_code' => 'RESOLVED_BUYER_FAVOUR',
                'amount_refunded' => [
                    'currency_code' => 'GBP',
                    'value' => '8.00',
                ],
            ],
            'adjudications' => [
                0 => [
                    'type' => 'RECOVER_FROM_SELLER',
                    'adjudication_time' => '2020-12-01T14:33:01.000Z',
                    'reason' => 'INELIGIBLE_SELLER_PROTECTION_POLICY',
                    'dispute_life_cycle_stage' => 'CHARGEBACK',
                ],
            ],
            'money_movements' => [
                0 => [
                    'affected_party' => 'SELLER',
                    'amount' => [
                        'currency_code' => 'GBP',
                        'value' => '84.64',
                    ],
                    'initiated_time' => '2020-12-01T14:33:01.000Z',
                    'type' => 'DEBIT',
                    'reason' => 'DISPUTE_SETTLEMENT',
                ],
            ],
            'dispute_life_cycle_stage' => 'CHARGEBACK',
            'dispute_channel' => 'INTERNAL',
            'messages' => [
                0 => [
                    'posted_by' => 'BUYER',
                    'time_posted' => '2020-12-01T14:32:36.000Z',
                    'content' => 'Why do you have charged a wrong price? Please correct that.',
                ],
            ],
            'extensions' => [
                'merchant_contacted' => true,
                'billing_dispute_properties' => [
                    'incorrect_transaction_amount' => [
                        'correct_transaction_amount' => [
                            'currency_code' => 'GBP',
                            'value' => '12',
                        ],
                    ],
                ],
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

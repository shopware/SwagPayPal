<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class GetDisputesList
{
    public const FIRST_ID = 'PP-D-33305';

    public static function get(): array
    {
        return [
            'items' => [
                0 => [
                    'dispute_id' => self::FIRST_ID,
                    'create_time' => '2020-12-01T14:32:23.000Z',
                    'update_time' => '2020-12-01T14:33:01.000Z',
                    'reason' => 'INCORRECT_AMOUNT',
                    'status' => 'RESOLVED',
                    'dispute_state' => 'RESOLVED',
                    'dispute_amount' => [
                        'currency_code' => 'GBP',
                        'value' => '8.00',
                    ],
                    'dispute_life_cycle_stage' => 'INQUIRY',
                    'links' => [
                        0 => [
                            'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes/' . self::FIRST_ID,
                            'rel' => 'self',
                            'method' => 'GET',
                        ],
                    ],
                ],
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes',
                    'rel' => 'self',
                    'method' => 'GET',
                    'encType' => 'application/json',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes',
                    'rel' => 'first',
                    'method' => 'GET',
                    'encType' => 'application/json',
                ],
            ],
        ];
    }
}

<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class GetDisputesList
{
    public const LAST_ID = 'PP-D-33305';

    public static function get(): array
    {
        return [
            'items' => [
                0 => [
                    'dispute_id' => 'PP-D-35561',
                    'create_time' => '2021-01-04T10:02:46.000Z',
                    'update_time' => '2021-01-04T10:03:31.000Z',
                    'reason' => 'MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED',
                    'status' => 'WAITING_FOR_SELLER_RESPONSE',
                    'dispute_state' => 'REQUIRED_ACTION',
                    'dispute_amount' => [
                        'currency_code' => 'EUR',
                        'value' => '25.00',
                    ],
                    'dispute_life_cycle_stage' => 'INQUIRY',
                    'seller_response_due_date' => '2021-01-24T10:02:46.000Z',
                    'links' => [
                        0 => [
                            'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes/PP-D-35561',
                            'rel' => 'self',
                            'method' => 'GET',
                        ],
                    ],
                ],
                1 => [
                    'dispute_id' => 'PP-D-35558',
                    'create_time' => '2021-01-04T09:50:35.000Z',
                    'update_time' => '2021-01-04T09:50:59.000Z',
                    'reason' => 'INCORRECT_AMOUNT',
                    'status' => 'RESOLVED',
                    'dispute_state' => 'RESOLVED',
                    'dispute_amount' => [
                        'currency_code' => 'CHF',
                        'value' => '14.29',
                    ],
                    'dispute_life_cycle_stage' => 'INQUIRY',
                    'links' => [
                        0 => [
                            'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes/PP-D-35558',
                            'rel' => 'self',
                            'method' => 'GET',
                        ],
                    ],
                ],
                2 => [
                    'dispute_id' => self::LAST_ID,
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
                            'href' => 'https://api.sandbox.paypal.com/v1/customer/disputes/' . self::LAST_ID,
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

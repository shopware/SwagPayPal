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
class GetResourceMerchantIntegrations
{
    public const MERCHANT_ID = '7GHKL78J89JKX';
    public const TRACKING_ID = 'sb-gvyyd8675691@business.example.com';
    public const LEGAL_NAME = 'Test Store';

    public static function get(): array
    {
        return [
            'merchant_id' => self::MERCHANT_ID,
            'tracking_id' => self::TRACKING_ID,
            'products' => [
                [
                    'name' => 'PPCP_CUSTOM',
                    'vetting_status' => 'SUBSCRIBED',
                    'capabilities' => [
                        'CARD_PROCESSING_VIRTUAL_TERMINAL',
                        'COMMERCIAL_ENTITY',
                        'CUSTOM_CARD_PROCESSING',
                        'DEBIT_CARD_SWITCH',
                        'FRAUD_TOOL_ACCESS',
                    ],
                ],
                [
                    'name' => 'PPCP_STANDARD',
                    'vetting_status' => 'SUBSCRIBED',
                    'capabilities' => [
                        'ALT_PAY_PROCESSING',
                        'RECEIVE_MONEY',
                        'SEND_MONEY',
                        'STANDARD_CARD_PROCESSING',
                        'WITHDRAW_MONEY',
                    ],
                ],
            ],
            'capabilities' => [
                [
                    'name' => 'CARD_PROCESSING_VIRTUAL_TERMINAL',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'COMMERCIAL_ENTITY',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'CUSTOM_CARD_PROCESSING',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'DEBIT_CARD_SWITCH',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'FRAUD_TOOL_ACCESS',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'ALT_PAY_PROCESSING',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'RECEIVE_MONEY',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'SEND_MONEY',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'STANDARD_CARD_PROCESSING',
                    'status' => 'ACTIVE',
                ],
                [
                    'name' => 'WITHDRAW_MONEY',
                    'status' => 'ACTIVE',
                ],
            ],
            'payments_receivable' => true,
            'legal_name' => self::LEGAL_NAME,
            'primary_email_confirmed' => true,
        ];
    }
}

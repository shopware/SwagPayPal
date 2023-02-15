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
class CaptureAuthorization
{
    public const ID = '4S350271L2282743T';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '1.00',
            ],
            'final_capture' => false,
            'seller_protection' => [
                'status' => 'ELIGIBLE',
                'dispute_categories' => [
                    0 => 'ITEM_NOT_RECEIVED',
                    1 => 'UNAUTHORIZED_TRANSACTION',
                ],
            ],
            'seller_receivable_breakdown' => [
                'gross_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '1.00',
                ],
                'paypal_fee' => [
                    'currency_code' => 'EUR',
                    'value' => '0.37',
                ],
                'net_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '0.63',
                ],
                'exchange_rate' => [
                ],
            ],
            'invoice_id' => 'asdfasdfsa',
            'status' => 'COMPLETED',
            'create_time' => '2020-08-19T14:27:58Z',
            'update_time' => '2020-08-19T14:27:58Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/' . self::ID . '/refund',
                    'rel' => 'refund',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/4M480963DE259211M',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

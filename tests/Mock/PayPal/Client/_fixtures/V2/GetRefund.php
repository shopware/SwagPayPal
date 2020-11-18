<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class GetRefund
{
    public const ID = '33791463E79430539';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '12.34',
            ],
            'note_to_payer' => 'testRefundNoteToPayer',
            'seller_payable_breakdown' => [
                'gross_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '12.34',
                ],
                'paypal_fee' => [
                    'currency_code' => 'EUR',
                    'value' => '0.23',
                ],
                'net_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '12.11',
                ],
                'total_refunded_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '12.34',
                ],
            ],
            'invoice_id' => 'testRefundInvoiceId',
            'status' => 'COMPLETED',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/refunds/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8F71337376765912W',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
            'create_time' => '2020-08-17T07:03:55-07:00',
            'update_time' => '2020-08-17T07:03:55-07:00',
        ];
    }
}

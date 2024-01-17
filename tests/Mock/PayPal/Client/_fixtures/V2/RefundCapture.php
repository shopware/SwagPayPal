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
class RefundCapture
{
    public const TOTAL_REFUNDED_AMOUNT_VALUE = '14.34';

    public static function get(): array
    {
        return [
            'id' => '05D06182CG6087525',
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '1.00',
            ],
            'note_to_payer' => 'testNoteToPayer',
            'seller_payable_breakdown' => [
                'gross_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '1.00',
                ],
                'paypal_fee' => [
                    'currency_code' => 'EUR',
                    'value' => '0.02',
                ],
                'net_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '0.98',
                ],
                'total_refunded_amount' => [
                    'currency_code' => 'EUR',
                    'value' => self::TOTAL_REFUNDED_AMOUNT_VALUE,
                ],
            ],
            'invoice_id' => 'testInvoiceId',
            'status' => 'COMPLETED',
            'create_time' => '2020-08-18T23:26:38-07:00',
            'update_time' => '2020-08-18T23:26:38-07:00',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/refunds/05D06182CG6087525',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8F71337376765912W',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

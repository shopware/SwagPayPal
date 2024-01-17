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
class GetCapture
{
    public const ID = '6F970540CL012725M';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'COMPLETED',
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '56.78',
            ],
            'final_capture' => false,
            'disbursement_mode' => 'INSTANT',
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
                    'value' => '56.78',
                ],
                'paypal_fee' => [
                    'currency_code' => 'EUR',
                    'value' => '1.43',
                ],
                'net_amount' => [
                    'currency_code' => 'EUR',
                    'value' => '55.35',
                ],
            ],
            'invoice_id' => 'testCaptureInvoiceId',
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
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/98J050951B687083U',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
            'create_time' => '2020-08-17T15:05:57Z',
            'update_time' => '2020-08-17T15:05:57Z',
        ];
    }
}

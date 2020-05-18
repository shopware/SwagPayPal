<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class RefundSaleResponseFixture
{
    public const REFUND_AMOUNT = '12.35';
    public const TEST_REFUND_INVOICE_NUMBER = 'testRefundInvoiceNumber';

    public static function get(): array
    {
        return [
            'id' => '9UJ167316W283554V',
            'create_time' => '2018-12-06T14:16:35Z',
            'update_time' => '2018-12-06T14:16:35Z',
            'state' => 'completed',
            'amount' => [
                'total' => self::REFUND_AMOUNT,
                'currency' => 'EUR',
            ],
            'refund_from_transaction_fee' => [
                'currency' => 'EUR',
                'value' => '0.24',
            ],
            'total_refunded_amount' => [
                'currency' => 'EUR',
                'value' => '24.70',
            ],
            'refund_from_received_amount' => [
                'currency' => 'EUR',
                'value' => '12.11',
            ],
            'sale_id' => '98N85671E85717541',
            'parent_payment' => 'PAY-0MX009757T271510FLQES2MA',
            'invoice_number' => self::TEST_REFUND_INVOICE_NUMBER,
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/refund/9UJ167316W283554V',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-0MX009757T271510FLQES2MA',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/sale/98N85671E85717541',
                    'rel' => 'sale',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

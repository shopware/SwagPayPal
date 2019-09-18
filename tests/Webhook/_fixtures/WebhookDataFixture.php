<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\_fixtures;

class WebhookDataFixture
{
    public static function get(): array
    {
        return [
            'id' => 'WH-2WR32451HC0233532-67976317FL4543714',
            'create_time' => '2014-10-23T17:23:52Z',
            'resource_type' => 'sale',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'summary' => 'A successful sale payment was made for $ 0.48 USD',
            'resource' => [
                'parent_payment' => 'PAY-1PA12106FU478450MKRETS4A',
                'update_time' => '2014-10-23T17:23:04Z',
                'amount' => [
                    'total' => '0.48',
                    'currency' => 'USD',
                    'details' => [
                        'subtotal' => '0.48',
                    ],
                ],
                'payment_mode' => 'ECHECK',
                'create_time' => '2014-10-23T17:22:56Z',
                'clearing_time' => '2014-10-30T07:00:00Z',
                'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
                'protection_eligibility' => 'ELIGIBLE',
                'transaction_fee' => [
                    'value' => '0.02',
                    'currency' => 'USD',
                ],
                'invoice_number' => 'SW12345',
                'links' => [
                    0 => [
                        'href' => 'https://api.paypal.com/v1/payments/sale/80021663DE681814L',
                        'rel' => 'self',
                        'method' => 'GET',
                    ],
                    1 => [
                        'href' => 'https://api.paypal.com/v1/payments/sale/80021663DE681814L/refund',
                        'rel' => 'refund',
                        'method' => 'POST',
                    ],
                    2 => [
                        'href' => 'https://api.paypal.com/v1/payments/payment/PAY-1PA12106FU478450MKRETS4A',
                        'rel' => 'parent_payment',
                        'method' => 'GET',
                    ],
                ],
                'id' => '80021663DE681814L',
                'state' => 'completed',
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.paypal.com/v1/notifications/webhooks-events/WH-2WR32451HC0233532-67976317FL4543714',
                    'rel' => 'self',
                    'method' => 'GET',
                    'encType' => 'application/json',
                ],
                1 => [
                    'href' => 'https://api.paypal.com/v1/notifications/webhooks-events/WH-2WR32451HC0233532-67976317FL4543714/resend',
                    'rel' => 'resend',
                    'method' => 'POST',
                    'encType' => 'application/json',
                ],
            ],
            'event_version' => '1.0',
        ];
    }
}

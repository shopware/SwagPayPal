<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class VoidAuthorizationResponseFixture
{
    public const VOID_ID = '8JH509685U6626604';

    public static function get(): array
    {
        return [
            'id' => self::VOID_ID,
            'create_time' => '2019-03-13T09:44:34Z',
            'update_time' => '2019-03-13T09:45:52Z',
            'amount' => [
                'total' => '41.85',
                'currency' => 'EUR',
                'details' => [
                    'subtotal' => '37.95',
                    'shipping' => '3.90',
                ],
            ],
            'state' => 'voided',
            'parent_payment' => 'PAYID-LSENBSA4V0732384Y371935U',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/8JH509685U6626604',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LSENBSA4V0732384Y371935U',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

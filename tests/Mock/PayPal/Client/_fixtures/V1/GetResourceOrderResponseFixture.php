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
class GetResourceOrderResponseFixture
{
    public const ID = 'O-7PS41727C2382141U';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'create_time' => '2018-12-06T09:50:14Z',
            'update_time' => '2018-12-06T09:50:14Z',
            'amount' => [
                'total' => '375.00',
                'currency' => 'EUR',
                'details' => [
                    'subtotal' => '315.13',
                    'tax' => '59.87',
                ],
            ],
            'state' => 'PENDING',
            'reason_code' => 'ORDER',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-35B10430TC590490WLQDJXTI',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/do-void',
                    'rel' => 'void',
                    'method' => 'POST',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/authorize',
                    'rel' => 'authorization',
                    'method' => 'POST',
                ],
                4 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/orders/O-7PS41727C2382141U/capture',
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
            ],
            'parent_payment' => 'PAY-35B10430TC590490WLQDJXTI',
        ];
    }
}

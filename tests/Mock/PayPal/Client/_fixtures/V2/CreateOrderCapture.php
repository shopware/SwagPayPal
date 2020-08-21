<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

class CreateOrderCapture
{
    public const ID = '9XG87361JT539825A';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'CREATED',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . self::ID,
                    'rel' => 'approve',
                    'method' => 'GET',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'update',
                    'method' => 'PATCH',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID . '/capture',
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}

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
class CaptureAuthorizationMinimal
{
    public static function get(): array
    {
        return [
            'id' => '6F970540CL012725M',
            'status' => 'COMPLETED',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/6F970540CL012725M',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/6F970540CL012725M/refund',
                    'rel' => 'refund',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/98J050951B687083U',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

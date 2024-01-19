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
class GetAuthorization
{
    public const ID = '98J050951B687083U';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'CREATED',
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '100.00',
            ],
            'seller_protection' => [
                'status' => 'ELIGIBLE',
                'dispute_categories' => [
                    0 => 'ITEM_NOT_RECEIVED',
                    1 => 'UNAUTHORIZED_TRANSACTION',
                ],
            ],
            'expiration_time' => '2020-09-15T13:17:16Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/' . self::ID . '/capture',
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/' . self::ID . '/void',
                    'rel' => 'void',
                    'method' => 'POST',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/authorizations/' . self::ID . '/reauthorize',
                    'rel' => 'reauthorize',
                    'method' => 'POST',
                ],
                4 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/5YK02325A2136392P',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
            'create_time' => '2020-08-17T13:17:16Z',
            'update_time' => '2020-08-17T13:17:16Z',
        ];
    }
}

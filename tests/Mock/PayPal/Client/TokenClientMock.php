<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Swag\PayPal\PayPal\Client\TokenClient;

class TokenClientMock extends TokenClient
{
    public const ACCESS_TOKEN = 'testAccessToken';

    public const TOKEN_TYPE = 'Bearer';

    public function getToken(): array
    {
        return [
            'scope' => 'https://uri.paypal.com/services/subscriptions https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://uri.paypal.com/services/applications/webhooks openid https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.*',
            'nonce' => '2018-11-28T09:55:25Z-e1ZbHti0TBbVkqQDZSsZ7YkzM5rpibcPAsW8wcS-hw',
            'access_token' => self::ACCESS_TOKEN,
            'token_type' => self::TOKEN_TYPE,
            'app_id' => 'APP-80W284485P519543T',
            'expires_in' => 32389,
        ];
    }
}

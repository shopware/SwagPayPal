<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class CreateTokenResponseFixture
{
    public const ACCESS_TOKEN = 'testAccessToken';

    public static function get(): array
    {
        return [
            'access_token' => self::ACCESS_TOKEN,
            'expires_in' => 7200,
        ];
    }

    public static function getError(): array
    {
        return [
            'error' => 'invalid_grant',
            'error_description' => 'Invalid assertion JWT',
        ];
    }
}

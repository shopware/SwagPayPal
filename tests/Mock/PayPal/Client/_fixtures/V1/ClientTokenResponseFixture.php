<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class ClientTokenResponseFixture
{
    public const CLIENT_TOKEN = 'eyJicmFpbnRyZWUiOnsiYXV0aG9yaXphdGlvbkZpbmdlcnByaW50IjoiYjA0MWE2M2JlMTM4M2NlZGUxZTI3OWFlNDlhMWIyNzZlY2FjOTYzOWU2NjlhMGIzODQyYTdkMTY3NzcwYmY0OHxtZXJjaGFudF9pZD1yd3dua3FnMnhnNTZobTJuJnB1YmxpY19rZXk9czlic3BuaGtxMmYzaDk0NCZjcmVhdGVkX2F0PTIwMTgtMTEtMTRUMTE6MTg6MDAuMTU3WiIsInZlcnNpb24iOiIzLXBheXBhbCJ9LCJwYXlwYWwiOnsiYWNjZXNzVG9rZW4iOiJBMjFBQUhNVExyMmctVDlhSTJacUZHUmlFZ0ZFZGRHTGwxTzRlX0lvdk9ESVg2Q3pSdW5BVy02TzI2MjdiWUJ2cDNjQ0FNWi1lTFBNc2NDWnN0bDUyNHJyUGhUQklJNlBBIn19';

    public static function get(): array
    {
        return [
            'client_token' => self::CLIENT_TOKEN,
            'expires_in' => 3600,
        ];
    }
}

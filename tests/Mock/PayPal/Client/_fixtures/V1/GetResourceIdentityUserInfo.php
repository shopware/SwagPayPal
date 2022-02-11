<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class GetResourceIdentityUserInfo
{
    public const PAYER_ID = '7GHKL78J89JKX';

    public static function get(): array
    {
        return [
            'user_id' => 'https://www.paypal.com/webapps/auth/identity/user/47-eMcFxOOoe4XZZoRcZI4QpZO4WDE4I7DZpFWcfqEC4',
            'sub' => 'https://www.paypal.com/webapps/auth/identity/user/47-eMcFxOOoe4XZZoRcZI4QpZO4WDE4I7DZpFWcfqEC4',
            'name' => 'John Doe',
            'payer_id' => self::PAYER_ID,
            'address' => [
                'street_address' => '',
                'locality' => '',
                'region' => 'Berlin',
                'postal_code' => '10715',
                'country' => 'DE',
            ],
            'verified_account' => 'true',
            'emails' => [
                0 => [
                    'value' => 'sb-gvyyd8675691@business.example.com',
                    'primary' => true,
                    'confirmed' => true,
                ],
            ],
        ];
    }
}

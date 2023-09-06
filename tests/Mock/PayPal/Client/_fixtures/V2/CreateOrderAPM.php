<?php declare(strict_types=1);
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
class CreateOrderAPM
{
    public const ID = '0RW08056D62072441';

    public static function get(string $apmName = 'sofort'): array
    {
        return [
            'id' => self::ID,
            'status' => 'PAYER_ACTION_REQUIRED',
            'payment_source' => [
                $apmName => [
                    'name' => 'Test User',
                    'country_code' => 'DE',
                ],
            ],
            'links' => [
                [
                    'href' => \sprintf('https://api.sandbox.paypal.com/v2/checkout/orders/%s', self::ID),
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => \sprintf('https://sandbox.paypal.com/payment/%s?token=%s', $apmName, self::ID),
                    'rel' => 'payer-action',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

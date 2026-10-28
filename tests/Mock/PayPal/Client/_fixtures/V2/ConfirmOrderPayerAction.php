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
class ConfirmOrderPayerAction
{
    public const ID = '5O190127TN364715T';
    public const PAYER_ACTION_URL = 'https://www.sandbox.paypal.com/checkoutnow?token=' . self::ID;

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'PAYER_ACTION_REQUIRED',
            'payment_source' => [
                'paypal' => [],
            ],
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => self::PAYER_ACTION_URL,
                    'rel' => 'payer-action',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

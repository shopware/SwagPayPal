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
class RefundCaptureMinimal
{
    public static function get(): array
    {
        return [
            'id' => '33791463E79430539',
            'status' => 'COMPLETED',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/refunds/33791463E79430539',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8F71337376765912W',
                    'rel' => 'up',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}

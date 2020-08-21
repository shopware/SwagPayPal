<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2;

final class PaymentStatusV2
{
    public const ORDERS_COMPLETED = 'COMPLETED';
    public const ORDERS_DECLINED = 'DECLINED';
    public const ORDERS_PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    public const ORDERS_PENDING = 'PENDING';
    public const ORDERS_REFUNDED = 'REFUNDED';

    private function __construct()
    {
    }
}

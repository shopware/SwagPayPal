<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PaymentStatusV2
{
    public const ORDER_CREATED = 'CREATED';
    public const ORDER_SAVED = 'SAVED';
    public const ORDER_APPROVED = 'APPROVED';
    public const ORDER_VOIDED = 'VOIDED';
    public const ORDER_COMPLETED = 'COMPLETED';
    public const ORDER_PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';

    public const ORDER_CAPTURE_COMPLETED = 'COMPLETED';
    public const ORDER_CAPTURE_DECLINED = 'DECLINED';
    public const ORDER_CAPTURE_PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    public const ORDER_CAPTURE_PENDING = 'PENDING';
    public const ORDER_CAPTURE_REFUNDED = 'REFUNDED';
    public const ORDER_CAPTURE_FAILED = 'FAILED';

    public const ORDER_AUTHORIZATION_CREATED = 'CREATED';
    public const ORDER_AUTHORIZATION_CAPTURED = 'CAPTURED';
    public const ORDER_AUTHORIZATION_DENIED = 'DENIED';
    public const ORDER_AUTHORIZATION_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';
    public const ORDER_AUTHORIZATION_VOIDED = 'VOIDED';
    public const ORDER_AUTHORIZATION_PENDING = 'PENDING';

    public const ORDER_REFUND_CANCELLED = 'CANCELLED';
    public const ORDER_REFUND_FAILED = 'FAILED';
    public const ORDER_REFUND_PENDING = 'PENDING';
    public const ORDER_REFUND_COMPLETED = 'COMPLETED';

    private function __construct()
    {
    }
}

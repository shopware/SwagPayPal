<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal;

final class PaymentStatus
{
    public const PAYMENT_COMPLETED = 'completed';
    public const PAYMENT_AUTHORIZED = 'authorized';
    public const PAYMENT_VOIDED = 'voided';
    public const PAYMENT_CAPTURED = 'captured';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    private function __construct()
    {
    }
}

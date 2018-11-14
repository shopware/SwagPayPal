<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal;

final class PaymentStatus
{
    /**
     * The default status from PayPal to identify completed transactions
     */
    public const PAYMENT_COMPLETED = 'completed';
}

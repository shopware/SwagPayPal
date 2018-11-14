<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class State
{
    /**
     * The transaction has completed.
     */
    public const COMPLETED = 'completed';

    /**
     * The transaction was partially refunded.
     */
    public const PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * The transaction is pending.
     */
    public const PENDING = 'pending';

    /**
     * The transaction was fully refunded.
     */
    public const REFUNDED = 'refunded';

    /**
     * The transaction was denied.
     */
    public const DENIED = 'denied';
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Log\Package;

/**
 * Whether a denied capture or a voided authorization may cancel a transaction, the same in the webhooks and in the Administration.
 *
 * @internal
 */
#[Package('checkout')]
final class CancellationPolicy
{
    // money was collected, or the transaction is cancelled already
    private const PROTECTED_STATES = [
        OrderTransactionStates::STATE_CANCELLED,
        OrderTransactionStates::STATE_PAID,
        OrderTransactionStates::STATE_PARTIALLY_PAID,
        OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
        OrderTransactionStates::STATE_REFUNDED,
    ];

    public static function isAllowed(OrderTransactionEntity $orderTransaction): bool
    {
        $state = $orderTransaction->getStateMachineState()?->getTechnicalName();

        return $state === null || !\in_array($state, self::PROTECTED_STATES, true);
    }
}

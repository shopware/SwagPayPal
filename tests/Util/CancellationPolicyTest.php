<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\Util\CancellationPolicy;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CancellationPolicy::class)]
class CancellationPolicyTest extends TestCase
{
    #[DataProvider('isAllowedProvider')]
    public function testIsAllowed(?string $state, bool $expected): void
    {
        $orderTransaction = new OrderTransactionEntity();
        if ($state !== null) {
            $orderTransaction->setStateMachineState((new StateMachineStateEntity())->assign(['technicalName' => $state]));
        }

        static::assertSame($expected, CancellationPolicy::isAllowed($orderTransaction));
    }

    /**
     * @return iterable<string, array{string|null, bool}>
     */
    public static function isAllowedProvider(): iterable
    {
        yield 'cancelled' => [OrderTransactionStates::STATE_CANCELLED, false];
        yield 'paid' => [OrderTransactionStates::STATE_PAID, false];
        yield 'partially paid' => [OrderTransactionStates::STATE_PARTIALLY_PAID, false];
        yield 'partially refunded' => [OrderTransactionStates::STATE_PARTIALLY_REFUNDED, false];
        yield 'refunded' => [OrderTransactionStates::STATE_REFUNDED, false];
        yield 'open' => [OrderTransactionStates::STATE_OPEN, true];
        yield 'authorized' => [OrderTransactionStates::STATE_AUTHORIZED, true];
        yield 'in progress' => [OrderTransactionStates::STATE_IN_PROGRESS, true];
        yield 'unconfirmed' => [OrderTransactionStates::STATE_UNCONFIRMED, true];
        yield 'reminded' => [OrderTransactionStates::STATE_REMINDED, true];
        yield 'failed' => [OrderTransactionStates::STATE_FAILED, true];
        yield 'chargeback' => [OrderTransactionStates::STATE_CHARGEBACK, true];
        yield 'state not loaded' => [null, true];
    }
}

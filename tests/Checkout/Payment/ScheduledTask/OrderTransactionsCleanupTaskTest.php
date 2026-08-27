<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\ScheduledTask\OrderTransactionsCleanupTask;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionsCleanupTaskTest extends TestCase
{
    public function testTaskConfiguration(): void
    {
        static::assertSame('swag_paypal.order_transactions_cleanup', OrderTransactionsCleanupTask::getTaskName());
        static::assertSame(86400, OrderTransactionsCleanupTask::getDefaultInterval());
    }
}

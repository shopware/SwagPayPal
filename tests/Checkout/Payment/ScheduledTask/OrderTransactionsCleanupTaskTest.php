<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\ScheduledTask\OrderTransactionsCleanupTask;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderTransactionsCleanupTask::class)]
class OrderTransactionsCleanupTaskTest extends TestCase
{
    public function testTaskConfiguration(): void
    {
        static::assertSame('swag_paypal.order_transactions_cleanup', OrderTransactionsCleanupTask::getTaskName());
        static::assertSame(86400, OrderTransactionsCleanupTask::getDefaultInterval());
    }
}

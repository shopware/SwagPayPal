<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\ScheduledTask;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('checkout')]
class OrderTransactionsCleanupTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'swag_paypal.order_transactions_cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return 86400;
    }
}

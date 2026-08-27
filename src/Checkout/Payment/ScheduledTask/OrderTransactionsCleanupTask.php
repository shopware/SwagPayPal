<?php declare(strict_types=1);

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

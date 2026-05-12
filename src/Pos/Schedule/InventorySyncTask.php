<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('checkout')]
class InventorySyncTask extends ScheduledTask
{
    private const TIME_INTERVAL_FIVE_MINUTES = 300;

    public static function getTaskName(): string
    {
        return 'swag_paypal.pos_inventory_sync';
    }

    public static function getDefaultInterval(): int
    {
        return self::TIME_INTERVAL_FIVE_MINUTES;
    }
}

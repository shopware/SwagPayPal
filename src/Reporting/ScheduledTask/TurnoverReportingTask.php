<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Reporting\ScheduledTask;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class TurnoverReportingTask extends ScheduledTask
{
    private const TIME_INTERVAL = 86400;

    public static function getTaskName(): string
    {
        return 'swag_paypal.turnover_reporting';
    }

    public static function getDefaultInterval(): int
    {
        return self::TIME_INTERVAL;
    }
}

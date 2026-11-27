<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
enum Severity: string
{
    case Error = 'error';

    /**
     * Works only under some conditions
     */
    case Warning = 'warning';

    case Notice = 'notice';

    /**
     * The check couldn't tell, nothing is known to be broken
     */
    case Unknown = 'unknown';

    /**
     * Orders by the cases, the most severe first
     */
    public function compare(self $other): int
    {
        return \array_search($this, self::cases(), true) <=> \array_search($other, self::cases(), true);
    }
}

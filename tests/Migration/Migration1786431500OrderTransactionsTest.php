<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1786431500OrderTransactions::class)]
class Migration1786431500OrderTransactionsTest extends TestCase
{
    public function testCreationTimestamp(): void
    {
        static::assertSame(1786431500, (new Migration1786431500OrderTransactions())->getCreationTimestamp());
    }
}

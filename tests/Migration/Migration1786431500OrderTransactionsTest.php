<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1786431500OrderTransactionsTest extends TestCase
{
    public function testCreationTimestamp(): void
    {
        static::assertSame(1786431500, (new Migration1786431500OrderTransactions())->getCreationTimestamp());
    }
}

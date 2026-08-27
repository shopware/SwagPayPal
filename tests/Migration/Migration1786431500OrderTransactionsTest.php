<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Migration\Migration1786431500OrderTransactions;

#[Package('checkout')]
#[CoversClass(Migration1786431500OrderTransactions::class)]
class Migration1786431500OrderTransactionsTest extends TestCase
{
    public function testCreationTimestamp(): void
    {
        static::assertSame(1786431500, (new Migration1786431500OrderTransactions())->getCreationTimestamp());
    }
}

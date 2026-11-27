<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Log\Retention;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineDefinition;
use Swag\PayPal\Diagnostics\Log\Retention\LogCleanupTaskHandler;
use Symfony\Component\Clock\MockClock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogCleanupTaskHandler::class)]
class LogCleanupTaskHandlerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    // after the transaction started, as lines logged by earlier tests would count against the limits
    #[Before(-1)]
    public function emptyTable(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM `swag_paypal_log`');
    }

    public function testDeletesTheLinesOlderThanTheMaximumAge(): void
    {
        $this->insertLine('expired', '2026-10-27 11:59:59.999');
        $this->insertLine('on the limit', '2026-10-27 12:00:00.000');
        $this->insertLine('recent', '2026-11-25 12:00:00.000');

        $this->createHandler(maxAgeInDays: 30, maxLines: 100)->run();

        static::assertSame(['on the limit', 'recent'], $this->fetchMessages());
    }

    public function testKeepsTheNewestLinesOnly(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $index => $message) {
            $this->insertLine($message, '2026-11-2' . $index . ' 10:00:00.000');
        }

        $this->createHandler(maxAgeInDays: 30, maxLines: 2)->run();

        static::assertSame(['d', 'e'], $this->fetchMessages());
    }

    public function testKeepsEverythingWithinBothLimits(): void
    {
        $this->insertLine('a', '2026-11-20 10:00:00.000');
        $this->insertLine('b', '2026-11-21 10:00:00.000');

        $this->createHandler(maxAgeInDays: 30, maxLines: 2)->run();

        static::assertSame(['a', 'b'], $this->fetchMessages());
    }

    public function testDeletesInBatches(): void
    {
        for ($i = 0; $i < 7; ++$i) {
            $this->insertLine('old ' . $i, '2026-09-01 10:00:00.00' . $i);
        }
        $this->insertLine('recent', '2026-11-25 10:00:00.000');

        $this->createHandler(maxAgeInDays: 30, maxLines: 100, batchSize: 3)->run();

        static::assertSame(['recent'], $this->fetchMessages());
    }

    public function testDeletesInBatchesWhenCappingTheLines(): void
    {
        for ($i = 0; $i < 8; ++$i) {
            $this->insertLine('line ' . $i, '2026-11-20 10:00:00.00' . $i);
        }

        $this->createHandler(maxAgeInDays: 30, maxLines: 2, batchSize: 3)->run();

        static::assertSame(['line 6', 'line 7'], $this->fetchMessages());
    }

    private function createHandler(int $maxAgeInDays, int $maxLines, int $batchSize = 10000): LogCleanupTaskHandler
    {
        /** @var EntityRepository<ScheduledTaskCollection> $repository */
        $repository = static::getContainer()->get('scheduled_task.repository');

        return new LogCleanupTaskHandler(
            $repository,
            new NullLogger(),
            static::getContainer()->get(Connection::class),
            new MockClock('2026-11-26 12:00:00', 'UTC'),
            $maxAgeInDays,
            $maxLines,
            $batchSize,
        );
    }

    private function insertLine(string $message, string $createdAt): void
    {
        static::getContainer()->get(Connection::class)->insert(LogLineDefinition::ENTITY_NAME, [
            'id' => Uuid::randomBytes(),
            'level' => 400,
            'message' => $message,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @return list<string>
     */
    private function fetchMessages(): array
    {
        $messages = static::getContainer()->get(Connection::class)->fetchFirstColumn('SELECT `message` FROM `swag_paypal_log` ORDER BY `message`');

        return $messages;
    }
}

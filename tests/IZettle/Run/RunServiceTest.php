<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunLogEntity;
use Swag\PayPal\IZettle\Run\LoggerFactory;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunRepoMock;

class RunServiceTest extends TestCase
{
    private const TEST_MESSAGE = 'test';

    /**
     * @var RunRepoMock
     */
    private $runRepository;

    /**
     * @var RunLogRepoMock
     */
    private $logRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var Context
     */
    private $context;

    public function setUp(): void
    {
        $this->runRepository = new RunRepoMock();
        $this->logRepository = new RunLogRepoMock();
        $this->context = Context::createDefaultContext();

        $this->logger = (new LoggerFactory())->createLogger();
        $this->runService = new RunService($this->runRepository, $this->logRepository, $this->logger);
    }

    public function testLogProcessAddLogWithoutProduct(): void
    {
        $run = $this->runRepository->getFirstRun();
        static::assertNull($run);
        $runId = $this->runService->startRun(Defaults::SALES_CHANNEL, 'complete', $this->context);
        $run = $this->runRepository->getFirstRun();
        static::assertNotNull($run);

        $this->logger->info(self::TEST_MESSAGE);

        $this->runService->writeLog($runId, $this->context);
        $this->runService->finishRun($runId, $this->context);

        static::assertNotNull($run->getFinishedAt());

        $logEntry = $this->logRepository->getCollection()->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(IZettleSalesChannelRunLogEntity::class, $logEntry);
        static::assertEquals(Logger::INFO, $logEntry->getLevel());
        static::assertEquals(self::TEST_MESSAGE, $logEntry->getMessage());
        static::assertEquals($runId, $logEntry->getRunId());
        static::assertNull($logEntry->getProductId());
        static::assertNull($logEntry->getProductVersionId());
    }

    public function testLogProcessAddLogWithProduct(): void
    {
        $run = $this->runRepository->getFirstRun();
        static::assertNull($run);
        $runId = $this->runService->startRun(Defaults::SALES_CHANNEL, 'complete', $this->context);
        $run = $this->runRepository->getFirstRun();
        static::assertNotNull($run);

        $product = new SalesChannelProductEntity();
        $product->setId(Uuid::randomHex());
        $product->setVersionId(Uuid::randomHex());
        $product->setParentId(Uuid::randomHex());

        $this->logger->info(self::TEST_MESSAGE, ['product' => $product]);

        $this->runService->writeLog($runId, $this->context);
        $this->runService->finishRun($runId, $this->context);

        static::assertNotNull($run->getFinishedAt());

        $logEntry = $this->logRepository->getCollection()->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(IZettleSalesChannelRunLogEntity::class, $logEntry);
        static::assertEquals(Logger::INFO, $logEntry->getLevel());
        static::assertEquals(self::TEST_MESSAGE, $logEntry->getMessage());
        static::assertEquals($runId, $logEntry->getRunId());
        static::assertEquals($product->getParentId(), $logEntry->getProductId());
        static::assertEquals($product->getVersionId(), $logEntry->getProductVersionId());
    }

    public function testAbortRun(): void
    {
        $this->runService->startRun(Defaults::SALES_CHANNEL, 'complete', $this->context);

        $run = $this->runRepository->getFirstRun();
        static::assertNotNull($run);
        static::assertNull($run->getFinishedAt());
        static::assertCount(0, $this->logRepository->getCollection());

        $this->runService->abortRun($run->getId(), $this->context);

        static::assertNotNull($run->getFinishedAt());

        $logEntry = $this->logRepository->getCollection()->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(IZettleSalesChannelRunLogEntity::class, $logEntry);
        static::assertEquals(Logger::EMERGENCY, $logEntry->getLevel());
    }

    public function testIsRunActiveTrue(): void
    {
        $runId = $this->runService->startRun(Defaults::SALES_CHANNEL, 'complete', $this->context);
        static::assertTrue($this->runService->isRunActive($runId, $this->context));
    }

    public function testIsRunActiveFalse(): void
    {
        $runId = $this->runService->startRun(Defaults::SALES_CHANNEL, 'complete', $this->context);
        $this->runService->finishRun($runId, $this->context);
        static::assertFalse($this->runService->isRunActive($runId, $this->context));
    }

    public function testIsRunActiveNoRun(): void
    {
        static::assertFalse($this->runService->isRunActive(Uuid::randomHex(), $this->context));
    }
}

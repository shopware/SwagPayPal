<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogEntity;
use Swag\PayPal\Pos\Run\LoggerFactory;
use Swag\PayPal\Pos\Run\RunService;

/**
 * @internal
 */
#[Package('checkout')]
class RunServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const TEST_MESSAGE = 'test';

    private EntityRepository $runRepository;

    private EntityRepository $logRepository;

    private Logger $logger;

    private RunService $runService;

    private Context $context;

    protected function setUp(): void
    {
        /** @var EntityRepository $runRepository */
        $runRepository = $this->getContainer()->get('swag_paypal_pos_sales_channel_run.repository');
        $this->runRepository = $runRepository;
        /** @var EntityRepository $logRepository */
        $logRepository = $this->getContainer()->get('swag_paypal_pos_sales_channel_run_log.repository');
        $this->logRepository = $logRepository;
        $this->context = Context::createDefaultContext();

        $this->logger = (new LoggerFactory())->createLogger();
        $this->runService = new RunService($this->runRepository, $this->logRepository, $this->getContainer()->get(Connection::class), $this->logger);
    }

    public function testLogProcessAddLogWithoutProduct(): void
    {
        $context = Context::createDefaultContext();
        $runId = $this->runService->startRun(TestDefaults::SALES_CHANNEL, 'complete', [], $this->context);
        static::assertNotNull($this->runRepository->searchIds(new Criteria([$runId]), $context)->firstId());

        $this->logger->info(self::TEST_MESSAGE);

        $this->runService->writeLog($runId, $this->context);
        $this->runService->finishRun($runId, $this->context);

        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();
        static::assertNotNull($run);
        static::assertInstanceOf(PosSalesChannelRunEntity::class, $run);
        static::assertNotNull($run->getFinishedAt());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $logEntry = $this->logRepository->search($criteria, $context)->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(PosSalesChannelRunLogEntity::class, $logEntry);
        static::assertEquals(Level::Info, Level::from($logEntry->getLevel()));
        static::assertEquals(self::TEST_MESSAGE, $logEntry->getMessage());
        static::assertEquals($runId, $logEntry->getRunId());
        static::assertNull($logEntry->getProductId());
        static::assertNull($logEntry->getProductVersionId());
    }

    public function testLogProcessAddLogWithProduct(): void
    {
        $context = Context::createDefaultContext();
        $runId = $this->runService->startRun(TestDefaults::SALES_CHANNEL, 'complete', [], $this->context);
        static::assertNotNull($this->runRepository->searchIds(new Criteria([$runId]), $context)->firstId());

        $product = $this->createProduct($context);
        static::assertNotNull($product);
        $this->logger->info(self::TEST_MESSAGE, ['product' => $product]);

        $this->runService->writeLog($runId, $this->context);
        $this->runService->finishRun($runId, $this->context);

        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();
        static::assertNotNull($run);
        static::assertInstanceOf(PosSalesChannelRunEntity::class, $run);
        static::assertNotNull($run->getFinishedAt());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $logEntry = $this->logRepository->search($criteria, $context)->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(PosSalesChannelRunLogEntity::class, $logEntry);
        static::assertSame(Level::Info, Level::from($logEntry->getLevel()));
        static::assertEquals(self::TEST_MESSAGE, $logEntry->getMessage());
        static::assertEquals($runId, $logEntry->getRunId());
        static::assertEquals($product->getParentId(), $logEntry->getProductId());
        static::assertEquals($product->getVersionId(), $logEntry->getProductVersionId());
    }

    public function testAbortRun(): void
    {
        $context = Context::createDefaultContext();
        $runId = $this->runService->startRun(TestDefaults::SALES_CHANNEL, 'complete', [], $this->context);
        static::assertNotNull($this->runRepository->searchIds(new Criteria([$runId]), $context)->firstId());

        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();
        static::assertNotNull($run);
        static::assertInstanceOf(PosSalesChannelRunEntity::class, $run);
        static::assertNull($run->getFinishedAt());
        static::assertSame(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, $run->getStatus());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $logEntry = $this->logRepository->search($criteria, $context)->first();
        static::assertNull($logEntry);

        $this->runService->abortRun($run->getId(), $this->context);

        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();
        static::assertNotNull($run);
        static::assertInstanceOf(PosSalesChannelRunEntity::class, $run);
        static::assertNotNull($run->getFinishedAt());
        static::assertSame(PosSalesChannelRunDefinition::STATUS_CANCELLED, $run->getStatus());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $logEntry = $this->logRepository->search($criteria, $context)->first();
        static::assertNotNull($logEntry);
        static::assertInstanceOf(PosSalesChannelRunLogEntity::class, $logEntry);
        static::assertSame(Level::Emergency, Level::from($logEntry->getLevel()));
    }

    public function testIsRunActiveTrue(): void
    {
        $runId = $this->runService->startRun(TestDefaults::SALES_CHANNEL, 'complete', [], $this->context);
        static::assertTrue($this->runService->isRunActive($runId, $this->context));
    }

    public function testIsRunActiveFalse(): void
    {
        $runId = $this->runService->startRun(TestDefaults::SALES_CHANNEL, 'complete', [], $this->context);
        $this->runService->finishRun($runId, $this->context);
        static::assertFalse($this->runService->isRunActive($runId, $this->context));
    }

    public function testIsRunActiveNoRun(): void
    {
        static::assertFalse($this->runService->isRunActive(Uuid::randomHex(), $this->context));
    }

    protected function createProduct(Context $context): ?ProductEntity
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');

        $data = [
            'name' => 'Test product',
            'productNumber' => '123456789',
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 19.99, 'net' => 10, 'linked' => false],
            ],
            'manufacturer' => ['name' => 'shopware AG'],
            'tax' => ['id' => $this->getValidTaxId(), 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories' => [
                ['name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $data['parent'] = $data;
        $data['id'] = Uuid::randomHex();
        $data['productNumber'] = 'aProductNumber';

        $productRepository->upsert([$data], $context);

        /** @var ProductEntity|null $product */
        $product = $productRepository->search(new Criteria([$data['id']]), $context)->first();

        return $product;
    }
}

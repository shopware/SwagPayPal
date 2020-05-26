<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Util;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunCollection;
use Swag\PayPal\IZettle\Run\LoggerFactory;
use Swag\PayPal\IZettle\Run\RunService;

class RunServiceTest extends TestCase
{
    public function testLogProcessAddLogWithoutProduct(): void
    {
        $runRepository = $this->createMock(EntityRepositoryInterface::class);
        $logRepository = $this->createMock(EntityRepositoryInterface::class);
        $context = Context::createDefaultContext();

        $logger = (new LoggerFactory())->createLogger();
        $runService = new RunService($runRepository, $logRepository, $logger);

        $runRepository->expects(static::once())->method('create');
        $run = $runService->startRun(Defaults::SALES_CHANNEL, $context);

        $logger->info('test');

        $runRepository->method('search')->willReturn(new EntitySearchResult(
            0,
            new IZettleSalesChannelRunCollection(),
            null,
            new Criteria(),
            $context
        ));
        $logRepository->expects(static::once())->method('create')->with([[
            'level' => Logger::INFO,
            'message' => 'test',
            'runId' => $run->getId(),
        ]]);
        $runService->finishRun($run, $context);
    }

    public function testLogProcessAddLogWithProduct(): void
    {
        $runRepository = $this->createMock(EntityRepositoryInterface::class);
        $logRepository = $this->createMock(EntityRepositoryInterface::class);
        $context = Context::createDefaultContext();

        $logger = (new LoggerFactory())->createLogger();
        $runService = new RunService($runRepository, $logRepository, $logger);

        $runRepository->expects(static::once())->method('create');
        $run = $runService->startRun(Defaults::SALES_CHANNEL, $context);

        $product = new ProductEntity();
        $product->setId(Uuid::randomHex());
        $product->setVersionId(Uuid::randomHex());
        $product->setParentId(Uuid::randomHex());

        $logger->info('test', ['product' => $product]);

        $runRepository->method('search')->willReturn(new EntitySearchResult(
            0,
            new IZettleSalesChannelRunCollection(),
            null,
            new Criteria(),
            $context
        ));
        $logRepository->expects(static::once())->method('create')->with([[
            'level' => Logger::INFO,
            'message' => 'test',
            'runId' => $run->getId(),
            'productId' => $product->getParentId(),
            'productVersionId' => $product->getVersionId(),
        ]]);
        $runService->finishRun($run, $context);
    }
}

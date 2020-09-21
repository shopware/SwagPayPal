<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogEntity;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;

class LogCleanerTest extends TestCase
{
    public function testLogCleanup(): void
    {
        $context = Context::createDefaultContext();
        $runRepository = $this->createLogHistory();
        $logCleaner = new LogCleaner($runRepository);

        static::assertCount(7, $runRepository->getCollection());

        $logCleaner->cleanUpLog(Defaults::SALES_CHANNEL, $context);

        static::assertCount(5, $runRepository->getCollection());
        static::assertEquals(['run1', 'run2', 'run3', 'run4', 'run6'], $runRepository->searchIds(new Criteria(), $context)->getIds());
    }

    public function testLogClear(): void
    {
        $context = Context::createDefaultContext();
        $runRepository = $this->createLogHistory();
        $logCleaner = new LogCleaner($runRepository);

        static::assertCount(7, $runRepository->getCollection());

        $logCleaner->clearLog(Defaults::SALES_CHANNEL, $context);

        static::assertEmpty($runRepository->getCollection());
    }

    private function createLogHistory(): RunRepoMock
    {
        $runRepository = new RunRepoMock();
        for ($i = 1; $i <= 7; ++$i) {
            $run = new PosSalesChannelRunEntity();
            $run->setId("run$i");
            $run->setCreatedAt(new \DateTime("-$i hours"));
            if ($i === 7) {
                $run->setCreatedAt(new \DateTime('-2 months'));
            }

            $logs = new PosSalesChannelRunLogCollection();

            $logAlways = new PosSalesChannelRunLogEntity();
            $logAlways->setId(Uuid::randomHex());
            $logAlways->setProductId('alwaysExistingProductId');
            $logs->add($logAlways);

            if ($i === 4 || $i === 7) {
                $logSometimes = new PosSalesChannelRunLogEntity();
                $logSometimes->setId(Uuid::randomHex());
                $logSometimes->setProductId('lessThanThreeTimesExistingProductId');
                $logs->add($logSometimes);
            }

            if ($i === 6) {
                $logNoProductReference = new PosSalesChannelRunLogEntity();
                $logNoProductReference->setId(Uuid::randomHex());
                $logs->add($logNoProductReference);
            }

            $run->setLogs($logs);
            $runRepository->addMockEntity($run);
        }

        return $runRepository;
    }
}

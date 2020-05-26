<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunLogCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunLogEntity;
use Swag\PayPal\IZettle\Run\LogCleaner;

class LogCleanerTest extends TestCase
{
    public function testLogCleanup(): void
    {
        $runRepository = $this->createMock(EntityRepositoryInterface::class);
        $context = Context::createDefaultContext();

        $logCleaner = new LogCleaner($runRepository);

        $runs = $this->createLogHistory();
        $runRepository->method('search')->willReturn(new EntitySearchResult(
            $runs->count(),
            $runs,
            null,
            new Criteria(),
            $context
        ));

        $runRepository->expects(static::once())->method('delete')->with([
            ['id' => 'run5'],
            ['id' => 'run7'],
        ]);

        $logCleaner->cleanUpLog(Defaults::SALES_CHANNEL, $context);
    }

    private function createLogHistory(): IZettleSalesChannelRunCollection
    {
        $runCollection = new IZettleSalesChannelRunCollection();
        for ($i = 1; $i <= 7; ++$i) {
            $run = new IZettleSalesChannelRunEntity();
            $run->setId("run$i");
            $run->setCreatedAt(new \DateTime("-$i hours"));
            if ($i === 7) {
                $run->setCreatedAt(new \DateTime('-2 months'));
            }

            $logs = new IZettleSalesChannelRunLogCollection();

            $logAlways = new IZettleSalesChannelRunLogEntity();
            $logAlways->setId(Uuid::randomHex());
            $logAlways->setProductId('alwaysExistingProductId');
            $logs->add($logAlways);

            if ($i === 4 || $i === 7) {
                $logSometimes = new IZettleSalesChannelRunLogEntity();
                $logSometimes->setId(Uuid::randomHex());
                $logSometimes->setProductId('lessThanThreeTimesExistingProductId');
                $logs->add($logSometimes);
            }

            if ($i === 6) {
                $logNoProductReference = new IZettleSalesChannelRunLogEntity();
                $logNoProductReference->setId(Uuid::randomHex());
                $logs->add($logNoProductReference);
            }

            $run->setLogs($logs);
            $runCollection->add($run);
        }

        return $runCollection;
    }
}

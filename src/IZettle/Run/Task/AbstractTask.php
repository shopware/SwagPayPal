<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Task;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Exception\UnexpectedSalesChannelTypeException;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\SwagPayPal;

abstract class AbstractTask
{
    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RunService $runService, LoggerInterface $logger)
    {
        $this->runService = $runService;
        $this->logger = $logger;
    }

    public function execute(SalesChannelEntity $salesChannel, Context $context): void
    {
        $run = $this->runService->startRun($salesChannel->getId(), $this->getRunTaskName(), $context);
        try {
            $this->run($salesChannel, $context);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());

            throw $e;
        } finally {
            $this->runService->finishRun($run, $context);
        }
    }

    abstract public function getRunTaskName(): string;

    abstract protected function run(SalesChannelEntity $salesChannel, Context $context): void;

    protected function getIZettleSalesChannel(SalesChannelEntity $salesChannel): IZettleSalesChannelEntity
    {
        /** @var IZettleSalesChannelEntity|null $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        if ($iZettleSalesChannel === null) {
            throw new UnexpectedSalesChannelTypeException($salesChannel->getTypeId());
        }

        return $iZettleSalesChannel;
    }
}

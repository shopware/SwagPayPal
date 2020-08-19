<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Swag\PayPal\IZettle\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\IZettle\Run\RunService;

abstract class AbstractSyncHandler extends AbstractMessageHandler
{
    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger
    ) {
        $this->runService = $runService;
        $this->logger = $logger;
    }

    /**
     * @param AbstractSyncMessage $message
     */
    public function handle($message): void
    {
        $runId = $message->getRunId();
        $context = $message->getContext();

        if (!$this->runService->isRunActive($runId, $context)) {
            return;
        }

        try {
            $this->sync($message);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());
        } finally {
            $this->runService->writeLog($runId, $context);
        }
    }

    abstract protected function sync(AbstractSyncMessage $message): void;
}

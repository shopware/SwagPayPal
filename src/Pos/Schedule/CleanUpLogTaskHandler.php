<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;

class CleanUpLogTaskHandler extends AbstractSyncTaskHandler
{
    /**
     * @var LogCleaner
     */
    private $logCleaner;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $salesChannelRepository,
        LogCleaner $logCleaner
    ) {
        parent::__construct($scheduledTaskRepository, $salesChannelRepository);
        $this->logCleaner = $logCleaner;
    }

    public static function getHandledMessages(): iterable
    {
        return [CleanUpLogTask::class];
    }

    protected function executeTask(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->logCleaner->cleanUpLog($salesChannel->getId(), $context);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Administration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunCollection;

#[Package('checkout')]
class LogCleaner
{
    private const LOG_RETENTION_PERIOD = 30;
    private const LOG_RETENTION_PER_PRODUCT = 3;

    private EntityRepository $runRepository;

    /**
     * @internal
     */
    public function __construct(EntityRepository $runRepository)
    {
        $this->runRepository = $runRepository;
    }

    public function cleanUpLog(string $salesChannelId, Context $context): void
    {
        $deletable = [];

        $criteria = new Criteria();
        $criteria->addAssociation('logs');
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        /** @var PosSalesChannelRunCollection $runs */
        $runs = $this->runRepository->search($criteria, $context)->getEntities();

        $now = new \DateTime();
        $logsPerProduct = [];

        foreach ($runs as $run) {
            $createdAt = $run->getCreatedAt();
            if ($createdAt !== null && $createdAt->diff($now)->days > self::LOG_RETENTION_PERIOD) {
                $deletable[] = ['id' => $run->getId()];

                continue;
            }

            $runUnnecessary = true;

            $logs = $run->getLogs() ?? [];
            foreach ($logs as $log) {
                $productId = $log->getProductId();
                if ($productId === null) {
                    $runUnnecessary = false;

                    continue;
                }
                if (!isset($logsPerProduct[$productId])) {
                    $logsPerProduct[$productId] = 0;
                }
                ++$logsPerProduct[$productId];
                if ($logsPerProduct[$productId] <= self::LOG_RETENTION_PER_PRODUCT) {
                    $runUnnecessary = false;
                }
            }

            if ($runUnnecessary && $run !== $runs->first()) {
                $deletable[] = ['id' => $run->getId()];
            }
        }

        if (\count($deletable) > 0) {
            $this->runRepository->delete($deletable, $context);
        }
    }

    public function clearLog(string $salesChannelId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $runIds = $this->runRepository->searchIds($criteria, $context)->getIds();
        if (!empty($runIds)) {
            $this->runRepository->delete(\array_filter(\array_map(static function ($id) {
                if (!\is_string($id)) {
                    return null;
                }

                return ['id' => $id];
            }, $runIds)), $context);
        }
    }
}

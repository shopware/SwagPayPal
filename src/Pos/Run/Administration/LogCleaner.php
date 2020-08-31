<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Administration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class LogCleaner
{
    private const LOG_RETENTION_PERIOD = 30;
    private const LOG_RETENTION_PER_PRODUCT = 3;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepository;

    public function __construct(EntityRepositoryInterface $runRepository)
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
        $runs = $this->runRepository->search($criteria, $context);

        $now = new \DateTime();
        $logsPerProduct = [];

        foreach ($runs as $run) {
            $createdAt = $run->getCreatedAt();
            if ($createdAt !== null && $createdAt->diff($now)->days > self::LOG_RETENTION_PERIOD) {
                $deletable[] = ['id' => $run->getId()];

                continue;
            }

            $runUnnecessary = true;
            foreach ($run->getLogs()->getElements() as $log) {
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
}

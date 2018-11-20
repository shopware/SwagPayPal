<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionState\OrderTransactionStateStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use SwagPayPal\Test\Helper\ConstantsForTesting;

class OrderTransactionStateRepoMock implements RepositoryInterface
{
    public const ORDER_TRANSACTION_STATE_ID = 'orderTransactionStateTestId';

    public const NO_TRANSACTION_STATE_RESULT = 'noTransactionStateResult';

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($context->hasExtension(self::NO_TRANSACTION_STATE_RESULT)) {
            return $this->createEntitySearchResultWithoutTransactionState($criteria, $context);
        }

        return $this->createEntitySearchResult($criteria, $context);
    }

    public function read(ReadCriteria $criteria, Context $context): EntityCollection
    {
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    private function createEntitySearchResultWithoutTransactionState(
        Criteria $criteria,
        Context $context
    ): EntitySearchResult {
        return new EntitySearchResult(
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITHOUT_RESULTS,
            new EntityCollection([]),
            null,
            $criteria,
            $context
        );
    }

    private function createEntitySearchResult(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS,
            $this->createEntityCollection(),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollection(): EntityCollection
    {
        return new EntityCollection([$this->createOrderTransactionState()]);
    }

    private function createOrderTransactionState(): OrderTransactionStateStruct
    {
        $orderTransaction = new OrderTransactionStateStruct();
        $orderTransaction->setId(self::ORDER_TRANSACTION_STATE_ID);

        return $orderTransaction;
    }
}

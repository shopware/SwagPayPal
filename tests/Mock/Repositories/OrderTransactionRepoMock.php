<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\Test\Helper\ConstantsForTesting;

class OrderTransactionRepoMock extends AbstractRepoMock
{
    public const ORDER_TRANSACTION_ID = 'orderTransactionTestId';

    public const WEBHOOK_PAYMENT_ID = 'webhookIdWithTransaction';

    public const WEBHOOK_WITHOUT_TRANSACTION = 'webhookIdWithoutTransaction';

    private array $data = [];

    public function getDefinition(): EntityDefinition
    {
        return new OrderTransactionDefinition();
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $filters = $criteria->getFilters();
        $filter = null;
        if ($filters !== []) {
            $filter = $filters[0];
        }

        if ($filter instanceof EqualsFilter && $filter->getValue() === self::WEBHOOK_WITHOUT_TRANSACTION) {
            return $this->createEntitySearchResultWithoutTransaction($criteria, $context);
        }

        if ($context->hasExtension(ConstantsForTesting::WITHOUT_TRANSACTION)) {
            return $this->createEntitySearchResultWithoutTransaction($criteria, $context);
        }

        if ($context->hasExtension(ConstantsForTesting::WITHOUT_ORDER)) {
            return $this->createEntitySearchResult($criteria, $context, false);
        }

        return $this->createEntitySearchResult($criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        $this->data = \array_merge_recursive($this->data, $data[0]);

        return parent::update($data, $context);
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function createEntitySearchResult(
        Criteria $criteria,
        Context $context,
        bool $withOrder = true
    ): EntitySearchResult {
        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITH_RESULTS,
            $this->createEntityCollection($withOrder),
            null,
            $criteria,
            $context
        );
    }

    private function createEntityCollection(bool $withOrder = true): EntityCollection
    {
        return new EntityCollection([$this->createOrderTransaction($withOrder)]);
    }

    private function createOrderTransaction(bool $withOrder = true): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(self::ORDER_TRANSACTION_ID);
        if ($withOrder) {
            $order = $this->createOrder();
            $orderTransaction->setOrder($order);
            $orderTransaction->setOrderId($order->getId());
        }

        return $orderTransaction;
    }

    private function createEntitySearchResultWithoutTransaction(
        Criteria $criteria,
        Context $context
    ): EntitySearchResult {
        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            ConstantsForTesting::REPO_SEARCH_RESULT_TOTAL_WITHOUT_RESULTS,
            new EntityCollection([]),
            null,
            $criteria,
            $context
        );
    }

    private function createOrder(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId('testOrderId');
        $order->setSalesChannelId(Defaults::SALES_CHANNEL);

        return $order;
    }
}

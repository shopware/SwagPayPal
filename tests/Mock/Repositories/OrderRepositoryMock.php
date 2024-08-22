<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;

/**
 * @internal
 */
#[Package('checkout')]
class OrderRepositoryMock extends AbstractRepoMock
{
    use PaymentTransactionTrait;

    public const NO_ORDER = 'searchResultWithoutOrder';
    public const NO_ORDER_TRANSACTIONS = 'searchResultWithoutOrderTransactions';
    public const NO_ORDER_TRANSACTION = 'searchResultWithoutOrderTransaction';

    public function getDefinition(): EntityDefinition
    {
        return new OrderDefinition();
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($context->hasExtension(self::NO_ORDER)) {
            $orderCollection = new OrderCollection([]);
        } elseif ($context->hasExtension(self::NO_ORDER_TRANSACTIONS)) {
            $orderEntity = $this->getOrderEntity();
            $orderEntity->assign(['transactions' => null]);
            $orderCollection = new OrderCollection([$orderEntity]);
        } elseif ($context->hasExtension(self::NO_ORDER_TRANSACTION)) {
            $orderEntity = $this->getOrderEntity();
            $orderEntity->setTransactions(new OrderTransactionCollection());
            $orderCollection = new OrderCollection([$orderEntity]);
        } else {
            $orderCollection = new OrderCollection([$this->getOrderEntity()]);
        }

        /** @var EntitySearchResult $result */
        $result = new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            \count($orderCollection),
            $orderCollection,
            null,
            $criteria,
            $context
        );

        return $result;
    }

    private function getOrderEntity(): OrderEntity
    {
        $orderEntity = $this->createOrderEntity(ConstantsForTesting::VALID_ORDER_ID);

        $orderTransaction = $this->createOrderTransaction();
        $orderTransaction->setOrderId($orderEntity->getId());

        $orderEntity->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        return $orderEntity;
    }
}

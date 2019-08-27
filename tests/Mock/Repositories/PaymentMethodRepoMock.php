<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Swag\PayPal\Payment\PayPalPaymentHandler;

class PaymentMethodRepoMock implements EntityRepositoryInterface
{
    public const PAYPAL_PAYMENT_METHOD_ID = '0afca95b4937428a884830cd516fb826';
    public const VERSION_ID_WITHOUT_PAYMENT_METHOD = 'WITHOUT_PAYMENT_METHOD';

    public function getDefinition(): EntityDefinition
    {
        return new PaymentMethodDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        if ($context->getVersionId() === self::VERSION_ID_WITHOUT_PAYMENT_METHOD) {
            return $this->getIdSearchResult(false, $criteria, $context);
        }

        /** @var EqualsFilter $firstFilter */
        $firstFilter = $criteria->getFilters()[0];
        if ($firstFilter->getValue() === PayPalPaymentHandler::class) {
            return $this->getIdSearchResult(true, $criteria, $context);
        }
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
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

    private function getIdSearchResult(bool $handlerFound, Criteria $criteria, Context $context): IdSearchResult
    {
        if ($handlerFound) {
            return new IdSearchResult(
                1,
                [
                    [
                        'primaryKey' => self::PAYPAL_PAYMENT_METHOD_ID,
                        'data' => [
                            'id' => self::PAYPAL_PAYMENT_METHOD_ID,
                        ],
                    ],
                ],
                $criteria,
                $context
            );
        }

        return new IdSearchResult(
            0,
            [],
            $criteria,
            $context
        );
    }
}

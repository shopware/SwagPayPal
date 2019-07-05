<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SalesChannelRepoMock implements EntityRepositoryInterface
{
    public const SALES_CHANNEL_NAME = 'SwagPayPal Test SalesChannel';

    public function getDefinition(): EntityDefinition
    {
        return new SalesChannelDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            1,
            new EntityCollection([
                Defaults::SALES_CHANNEL => $this->createSalesChannelEntity(
                    $criteria->getIds()[0] === Defaults::SALES_CHANNEL
                ),
            ]),
            null,
            $criteria,
            $context
        );
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

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    private function createSalesChannelEntity(bool $withPaymentMethods = false): SalesChannelEntity
    {
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId(
            $withPaymentMethods ? Defaults::SALES_CHANNEL : 'c3adfab083bf4182b44d880c209caa98'
        );
        $salesChannelEntity->setName(self::SALES_CHANNEL_NAME);

        if ($withPaymentMethods) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID);
            $salesChannelEntity->setPaymentMethods(
                new PaymentMethodCollection([
                    $paymentMethod,
                ])
            );
        }

        return $salesChannelEntity;
    }
}

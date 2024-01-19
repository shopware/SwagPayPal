<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Test\Util\PaymentMethodUtilTest;

/**
 * @internal
 */
#[Package('checkout')]
class SalesChannelRepoMock extends AbstractRepoMock
{
    public const SALES_CHANNEL_NAME = 'SwagPayPal Test SalesChannel';

    private array $updateData = [];

    public function getDefinition(): EntityDefinition
    {
        return new SalesChannelDefinition();
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        /** @var string[] $ids */
        $ids = $criteria->getIds();
        $id = $ids[0] ?? null;
        $withPaymentMethods = $id === TestDefaults::SALES_CHANNEL;
        $salesChannelId = $id ?? TestDefaults::SALES_CHANNEL;
        $arrayKey = $withPaymentMethods ? $salesChannelId : PaymentMethodUtilTest::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD;

        /** @var EntitySearchResult $result */
        $result = new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            1,
            new SalesChannelCollection([$arrayKey => $this->createSalesChannelEntity($salesChannelId, $withPaymentMethods)]),
            null,
            $criteria,
            $context
        );

        return $result;
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        $this->updateData = $data;

        return parent::update($data, $context);
    }

    public function getUpdateData(): array
    {
        return $this->updateData;
    }

    private function createSalesChannelEntity(string $id, bool $withPaymentMethods = false): SalesChannelEntity
    {
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId(
            $withPaymentMethods ? $id : PaymentMethodUtilTest::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD
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

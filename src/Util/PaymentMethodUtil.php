<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;

class PaymentMethodUtil
{
    private EntityRepositoryInterface $paymentRepository;

    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function getPayPalPaymentMethodId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class));

        return $this->paymentRepository->searchIds($criteria, $context)->firstId();
    }

    /**
     * @deprecated tag:v5.0.0 - will be removed without replacement
     */
    public function getPayPalPuiPaymentMethodId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPuiPaymentHandler::class));

        return $this->paymentRepository->searchIds($criteria, $context)->firstId();
    }

    public function isPaypalPaymentMethodInSalesChannel(
        SalesChannelContext $salesChannelContext,
        ?PaymentMethodCollection $paymentMethods = null
    ): bool {
        $context = $salesChannelContext->getContext();
        $paypalPaymentMethodId = $this->getPayPalPaymentMethodId($context);
        if (!$paypalPaymentMethodId) {
            return false;
        }

        if ($paymentMethods === null) {
            $paymentMethods = $this->getSalesChannelPaymentMethods($salesChannelContext->getSalesChannel(), $context);
            if ($paymentMethods === null) {
                return false;
            }
        }

        return $paymentMethods->has($paypalPaymentMethodId);
    }

    public function setPayPalAsDefaultPaymentMethod(Context $context, ?string $salesChannelId): void
    {
        $payPalPaymentMethodId = $this->getPayPalPaymentMethodId($context);
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $salesChannelsToChange = $this->getSalesChannelsToChange($context, $salesChannelId);
        $updateData = [];

        foreach ($salesChannelsToChange as $salesChannel) {
            $salesChannelUpdateData = [
                'id' => $salesChannel->getId(),
                'paymentMethodId' => $payPalPaymentMethodId,
            ];

            $paymentMethodCollection = $salesChannel->getPaymentMethods();
            if ($paymentMethodCollection === null || $paymentMethodCollection->get($payPalPaymentMethodId) === null) {
                $salesChannelUpdateData['paymentMethods'][] = [
                    'id' => $payPalPaymentMethodId,
                ];
            }

            $updateData[] = $salesChannelUpdateData;
        }

        $this->salesChannelRepository->update($updateData, $context);
    }

    private function getSalesChannelPaymentMethods(
        SalesChannelEntity $salesChannelEntity,
        Context $context
    ): ?PaymentMethodCollection {
        $salesChannelId = $salesChannelEntity->getId();
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('paymentMethods');
        $criteria->getAssociation('paymentMethods')->addFilter(new EqualsFilter('active', true));
        /** @var SalesChannelEntity|null $result */
        $result = $this->salesChannelRepository->search($criteria, $context)->get($salesChannelId);

        if (!$result) {
            return null;
        }

        return $result->getPaymentMethods();
    }

    private function getSalesChannelsToChange(Context $context, ?string $salesChannelId): EntityCollection
    {
        if ($salesChannelId !== null) {
            $criteria = new Criteria([$salesChannelId]);
        } else {
            $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsAnyFilter('typeId', [
                    Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
                    Defaults::SALES_CHANNEL_TYPE_API,
                ])
            );
        }

        $criteria->addAssociation('paymentMethods');

        return $this->salesChannelRepository->search($criteria, $context)->getEntities();
    }
}

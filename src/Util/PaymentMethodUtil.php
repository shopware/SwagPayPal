<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
class PaymentMethodUtil implements ResetInterface
{
    private EntityRepository $salesChannelRepository;

    private Connection $connection;

    /**
     * @var array<class-string, string>
     */
    private ?array $paymentMethodIds = null;

    /**
     * @var string[]
     */
    private ?array $salesChannels = null;

    /**
     * @internal
     */
    public function __construct(
        Connection $connection,
        EntityRepository $salesChannelRepository,
    ) {
        $this->connection = $connection;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function getPayPalPaymentMethodId(Context $context): ?string
    {
        return $this->getPaymentMethodIdByHandler(PayPalPaymentHandler::class);
    }

    public function isPaypalPaymentMethodInSalesChannel(
        SalesChannelContext $salesChannelContext,
        ?PaymentMethodCollection $paymentMethods = null,
    ): bool {
        $context = $salesChannelContext->getContext();
        $paypalPaymentMethodId = $this->getPayPalPaymentMethodId($context);
        if (!$paypalPaymentMethodId) {
            return false;
        }

        if ($paymentMethods !== null) {
            return $paymentMethods->has($paypalPaymentMethodId);
        }

        $paymentMethods = $salesChannelContext->getSalesChannel()->getPaymentMethods();
        if ($paymentMethods !== null) {
            return $paymentMethods->filterByProperty('active', true)->has($paypalPaymentMethodId);
        }

        if ($this->salesChannels === null) {
            // skip repository for performance reasons
            $salesChannels = $this->connection->fetchFirstColumn(
                'SELECT LOWER(HEX(assoc.`sales_channel_id`))
                FROM `sales_channel_payment_method` AS assoc
                    LEFT JOIN `payment_method` AS pm
                        ON pm.`id` = assoc.`payment_method_id`
                WHERE
                    assoc.`payment_method_id` = ? AND
                    pm.`active` = 1',
                [Uuid::fromHexToBytes($paypalPaymentMethodId)]
            );

            $this->salesChannels = $salesChannels;
        }

        return \in_array($salesChannelContext->getSalesChannelId(), $this->salesChannels, true);
    }

    public function setPayPalAsDefaultPaymentMethod(Context $context, ?string $salesChannelId): void
    {
        $payPalPaymentMethodId = $this->getPayPalPaymentMethodId($context);
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $salesChannelsToChange = $this->getSalesChannelsToChange($context, $salesChannelId);
        $updateData = [];

        /** @var SalesChannelEntity $salesChannel */
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

    public function reset(): void
    {
        $this->paymentMethodIds = null;
        $this->salesChannels = null;
    }

    private function getPaymentMethodIdByHandler(string $handlerIdentifier): ?string
    {
        if ($this->paymentMethodIds === null) {
            /** @var array<class-string, string> $ids */
            $ids = $this->connection->fetchAllKeyValue('SELECT `handler_identifier`, LOWER(HEX(`id`)) FROM `payment_method`');

            $this->paymentMethodIds = $ids;
        }

        return $this->paymentMethodIds[$handlerIdentifier] ?? null;
    }

    private function getSalesChannelsToChange(Context $context, ?string $salesChannelId): SalesChannelCollection
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

        /** @var SalesChannelCollection $collection */
        $collection = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $collection;
    }
}

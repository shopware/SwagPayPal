<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
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
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
class PaymentMethodUtil implements ResetInterface
{
    /**
     * @var array<class-string, string> - array<handlerIdentifier, paymentMethodId>
     */
    private ?array $paymentMethodIds = null;

    /**
     * @var array<string, array<string, string>> - array<salesChannelId, array<handlerIdentifier, paymentMethodId>>
     */
    private array $salesChannels = [];

    /**
     * @internal
     *
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $salesChannelRepository,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
    ) {
    }

    /**
     * Checks if a payment method is active for a given sales channel (context).
     * This does not mean that the payment method is available in the sense of the {@see AvailabilityContext}.
     *
     * @param array<string|AbstractMethodData|PaymentMethodEntity>|null $handlerIdentifier - If `null` given, all PayPal handlers will be considered
     */
    public function isPaymentMethodActive(SalesChannelContext $salesChannelContext, ?array $handlerIdentifier = null): bool
    {
        $handlerIdentifier ??= $this->paymentMethodDataRegistry->getPaymentHandlers();

        if (!$handlerIdentifier) {
            return false;
        }

        $handlerIdentifier = \array_map($this->intoHandlerIdentifier(...), $handlerIdentifier);
        $handlerIdentifier = \array_flip($handlerIdentifier);

        if ($paymentMethods = $salesChannelContext->getSalesChannel()->getPaymentMethods()) {
            return (bool) $paymentMethods->firstWhere(static fn (PaymentMethodEntity $pm) => $pm->getActive() && isset($handlerIdentifier[$pm->getHandlerIdentifier()]));
        }

        $paymentMethodIds = $this->getAllPaymentMethodIdsPerSalesChannel($salesChannelContext->getSalesChannelId());
        foreach ($handlerIdentifier as $hi => $_) {
            if (isset($paymentMethodIds[$hi])) {
                return true;
            }
        }

        return false;
    }

    public function getPaymentMethodId(string|AbstractMethodData $handlerIdentifier): ?string
    {
        return $this->getAllPaymentMethodIds()[$this->intoHandlerIdentifier($handlerIdentifier)] ?? null;
    }

    public function getPayPalPaymentMethodId(Context $context): ?string
    {
        return $this->getPaymentMethodId(PayPalPaymentHandler::class);
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::isPaymentMethodActive}
     */
    public function isPaypalPaymentMethodInSalesChannel(
        SalesChannelContext $salesChannelContext,
        ?PaymentMethodCollection $paymentMethods = null,
    ): bool {
        if (!($paypalPaymentMethodId = $this->getPayPalPaymentMethodId($salesChannelContext->getContext()))) {
            return false;
        }

        if ($paymentMethods !== null) {
            return $paymentMethods->has($paypalPaymentMethodId);
        }

        return $this->isPaymentMethodActive($salesChannelContext, [PayPalMethodData::class]);
    }

    public function setPayPalAsDefaultPaymentMethod(Context $context, ?string $salesChannelId): void
    {
        if (!($payPalPaymentMethodId = $this->getPayPalPaymentMethodId($context))) {
            return;
        }

        $salesChannelsToChange = $this->getSalesChannelsToChange($context, $salesChannelId);
        $updateData = \array_values($salesChannelsToChange->map(static fn (SalesChannelEntity $salesChannel) => [
            'id' => $salesChannel->getId(),
            'paymentMethodId' => $payPalPaymentMethodId,
            ...($salesChannel->getPaymentMethods()?->get($payPalPaymentMethodId) ? [] : [
                'paymentMethods' => [['id' => $payPalPaymentMethodId]],
            ]),
        ]));

        $this->salesChannelRepository->update($updateData, $context);
    }

    public function reset(): void
    {
        $this->paymentMethodIds = null;
        $this->salesChannels = [];
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

        return $this->salesChannelRepository->search($criteria, $context)->getEntities();
    }

    private function intoHandlerIdentifier(string|AbstractMethodData|PaymentMethodEntity $pm): string
    {
        return match (true) {
            $pm instanceof PaymentMethodEntity => $pm->getHandlerIdentifier(),
            $pm instanceof AbstractMethodData => $pm->getHandler(),
            \is_a($pm, AbstractMethodData::class, true) => $this->paymentMethodDataRegistry->getPaymentMethod($pm)->getHandler(),
            default => $pm,
        };
    }

    /**
     * @return array<string, string> - array<handlerIdentifier, paymentMethodId>
     */
    private function getAllPaymentMethodIdsPerSalesChannel(string $salesChannelId): array
    {
        // get all active sales channel payment method ids mapped by their handler identifier
        return $this->salesChannels[$salesChannelId] ??= $this->connection->fetchAllKeyValue(
            'SELECT pm.`handler_identifier`, LOWER(HEX(sc_pm.`payment_method_id`))
                FROM `sales_channel_payment_method` AS sc_pm
                LEFT JOIN `payment_method` AS pm ON pm.`id` = sc_pm.`payment_method_id`
                WHERE sc_pm.`sales_channel_id` = ? AND pm.`active` = 1',
            [Uuid::fromHexToBytes($salesChannelId)]
        );
    }

    /**
     * @return array<string, string> - array<handlerIdentifier, paymentMethodId>
     */
    private function getAllPaymentMethodIds(): array
    {
        return $this->paymentMethodIds ??= $this->connection->fetchAllKeyValue('SELECT `handler_identifier`, LOWER(HEX(`id`)) FROM `payment_method`');
    }
}

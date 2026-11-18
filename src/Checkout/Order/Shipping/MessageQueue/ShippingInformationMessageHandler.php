<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping\MessageQueue;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Shipping\Tracker\Item;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Shipping\Tracker\ItemCollection;
use Shopware\PayPalSDK\Struct\V2\Order\Tracker;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler]
class ShippingInformationMessageHandler
{
    public function __construct(
        private readonly EntityRepository $orderDeliveryRepository,
        private readonly OrderResource $orderResource,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ShippingInformationMessage $message): void
    {
        $criteria = (new Criteria([$message->getOrderDeliveryId()]))
            ->addAssociation('order.transactions')
            ->addAssociation('order.lineItems')
            ->addAssociation('shippingMethod');

        /** @var OrderDeliveryEntity|null $orderDelivery */
        $orderDelivery = $this->orderDeliveryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        $orderTransaction = $orderDelivery?->getOrder()?->getTransactions()?->last();
        $orderLineItems = $orderDelivery?->getOrder()?->getLineItems() ?? new OrderLineItemCollection();
        $salesChannelId = $orderDelivery?->getOrder()?->getSalesChannelId();
        $shippingMethodName = $orderDelivery?->getShippingMethod()?->getTranslation('name') ?? $orderDelivery?->getShippingMethod()?->getId() ?? '';
        $carrier = $orderDelivery?->getShippingMethod()?->getCustomFieldsValue(SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER) ?: Tracker::CARRIER_OTHER;
        $carrierOtherName = $orderDelivery?->getShippingMethod()?->getCustomFieldsValue(SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER_OTHER_NAME) ?: $orderDelivery?->getShippingMethod()?->getTranslation('name') ?? '';
        $orderId = $orderTransaction?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);
        $partnerAttributionId = $orderTransaction?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID);

        if (!$orderDelivery || !$orderTransaction || !\is_string($salesChannelId) || !\is_string($carrierOtherName) || !\is_string($orderId) || !\is_string($partnerAttributionId)) {
            return;
        }

        $logContext = [
            LogKey::SALES_CHANNEL_ID => $salesChannelId,
            LogKey::ORDER_DELIVERY_ID => $orderDelivery->getId(),
            LogKey::ORDER_ID => $orderDelivery->getOrder()?->getId(),
            LogKey::ORDER_NUMBER => $orderDelivery->getOrder()?->getOrderNumber(),
            LogKey::PAYPAL_ORDER_ID => $orderId,
        ];

        try {
            $order = $this->orderResource->get($orderId, $salesChannelId);
        } catch (PayPalApiException $e) {
            if ($e->is(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND)) {
                $this->logger->warning(
                    'Could not send the tracking codes of order {orderNumber}: PayPal couldn\'t find PayPal order {paypalOrderId}',
                    [...$logContext, LogKey::CODE => LogCode::TRACKING_PAYPAL_ORDER_NOT_FOUND, LogKey::EXCEPTION => $e],
                );

                return;
            }

            throw $e;
        }

        $captureId = $order->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->last()?->getId();

        if (!$captureId) {
            return;
        }

        $orderTrackers = $order->getPurchaseUnits()->first()?->getShipping()?->getTrackers()?->getTrackerCodes() ?? [];
        $deliveryTrackers = $this->trimTrackers($orderDelivery->getTrackingCodes());
        $itemCollection = $this->createItemCollection($orderLineItems);

        $addedTrackingCodes = \array_diff($deliveryTrackers, $orderTrackers);
        $removedTrackingCodes = \array_diff($orderTrackers, $deliveryTrackers);

        foreach ($addedTrackingCodes as $trackingCode) {
            $tracker = $this->createTracker($trackingCode, $captureId, $carrier, $carrierOtherName, $itemCollection);

            try {
                $this->orderResource->addTracker($tracker, $orderId, $salesChannelId, $partnerAttributionId);
            } catch (PayPalApiException $e) {
                $this->handleInvalidCarrierException($e, $tracker, $shippingMethodName, $logContext);

                $carrierOtherName = $carrier;
                $carrier = Tracker::CARRIER_OTHER;

                $this->orderResource->addTracker($tracker, $orderId, $salesChannelId, $partnerAttributionId);
            }
        }

        if (\count($addedTrackingCodes) > 0) {
            $this->logger->info('Sent tracking codes {trackingCodes} of order {orderNumber} to PayPal', [
                ...$logContext,
                LogKey::CODE => LogCode::TRACKING_CODES_ADDED,
                LogKey::TRACKING_CODES => \implode(', ', $addedTrackingCodes),
                LogKey::CARRIER => $carrier,
            ]);
        }

        foreach ($removedTrackingCodes as $trackingCode) {
            $tracker = $this->createTracker($trackingCode, $captureId, $carrier, $carrierOtherName, $itemCollection);

            $this->orderResource->removeTracker($tracker, $orderId, $salesChannelId, $partnerAttributionId);
        }

        if (\count($removedTrackingCodes) > 0) {
            $this->logger->info('Removed tracking codes {trackingCodes} of order {orderNumber} at PayPal', [
                ...$logContext,
                LogKey::CODE => LogCode::TRACKING_CODES_REMOVED,
                LogKey::TRACKING_CODES => \implode(', ', $removedTrackingCodes),
                LogKey::CARRIER => $carrier,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $logContext
     */
    private function handleInvalidCarrierException(PayPalApiException $e, Tracker &$tracker, string $shippingMethodName, array $logContext): void
    {
        if ($e->is(PayPalApiException::ISSUE_INVALID_PARAMETER_VALUE) && \str_contains($e->getMessage(), '(/carrier)')) {
            $this->logger->warning(
                'PayPal doesn\'t support carrier {carrier} of shipping method {shippingMethodName}, sent the tracking codes with carrier OTHER instead',
                [
                    ...$logContext,
                    LogKey::CODE => LogCode::TRACKING_CARRIER_UNSUPPORTED,
                    LogKey::CARRIER => $tracker->getCarrier(),
                    LogKey::SHIPPING_METHOD_NAME => $shippingMethodName,
                    LogKey::EXCEPTION => $e,
                ],
            );

            $tracker->setCarrierNameOther($tracker->getCarrier());
            $tracker->setCarrier(Tracker::CARRIER_OTHER);
        } else {
            throw $e;
        }
    }

    /**
     * @param string[] $trackers
     *
     * @return string[]
     */
    private function trimTrackers(array $trackers): array
    {
        return \array_filter(\array_map(
            static fn (string $tracker) => \mb_substr($tracker, 0, Tracker::MAX_LENGTH_TRACKING_NUMBER),
            $trackers
        ));
    }

    private function createTracker(string $trackingCode, string $captureId, string $carrier, string $carrierOtherName, ItemCollection $items): Tracker
    {
        $tracker = new Tracker();
        $tracker->setCaptureId($captureId);
        $tracker->setCarrier($carrier);
        $tracker->setTrackingNumber($trackingCode);
        $tracker->setItems($items);

        if ($tracker->getCarrier() === Tracker::CARRIER_OTHER) {
            $tracker->setCarrierNameOther($carrierOtherName);
        }

        return $tracker;
    }

    private function createItemCollection(OrderLineItemCollection $lineItems): ItemCollection
    {
        $lineItems = \array_filter($lineItems->filterGoodsFlat(), static fn (OrderLineItemEntity $item) => (bool) $item->getParentId());

        return ItemCollection::createFromAssociative(
            \array_map(static fn (OrderLineItemEntity $item) => [
                'name' => \mb_substr($item->getLabel(), 0, Item::MAX_LENGTH_NAME),
                'quantity' => $item->getQuantity(),
                'sku' => \mb_substr($item->getPayload()['productNumber'] ?? '', 0, Item::MAX_LENGTH_SKU) ?: null,
            ], $lineItems)
        );
    }
}

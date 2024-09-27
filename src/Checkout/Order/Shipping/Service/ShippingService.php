<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessageHandler;
use Swag\PayPal\RestApi\V1\Api\Shipping;
use Swag\PayPal\RestApi\V1\Api\Shipping\Tracker;
use Swag\PayPal\RestApi\V1\Api\Shipping\TrackerCollection;
use Swag\PayPal\RestApi\V1\Resource\ShippingResource;
use Swag\PayPal\SwagPayPal;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement. {@see ShippingInformationMessageHandler}
 */
#[Package('checkout')]
class ShippingService
{
    private ShippingResource $shippingResource;

    private EntityRepository $salesChannelRepository;

    private EntityRepository $orderTransactionRepository;

    private EntityRepository $shippingMethodRepository;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        ShippingResource $shippingResource,
        EntityRepository $salesChannelRepository,
        EntityRepository $orderTransactionRepository,
        EntityRepository $shippingMethodRepository,
        LoggerInterface $logger,
    ) {
        $this->shippingResource = $shippingResource;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->logger = $logger;
    }

    /**
     * @param string[] $after
     * @param string[] $before
     */
    public function updateTrackingCodes(string $orderDeliveryId, array $after, array $before, Context $context): void
    {
        $addedTrackingCodes = \array_diff($after, $before);
        $removedTrackingCodes = \array_diff($before, $after);

        if (!$addedTrackingCodes && !$removedTrackingCodes) {
            return;
        }

        $transactionId = $this->getPayPalTransactionId($orderDeliveryId, $context);
        if ($transactionId === null) {
            return;
        }

        $carrier = $this->fetchCarrier($orderDeliveryId, $context);
        if ($carrier === null) {
            return;
        }

        $salesChannelId = $this->fetchOrderSalesChannelId($orderDeliveryId, $context);
        $this->addTrackers($addedTrackingCodes, $transactionId, $orderDeliveryId, $carrier, $salesChannelId);
        $this->removeTrackers($removedTrackingCodes, $transactionId, $orderDeliveryId, $carrier, $salesChannelId);
    }

    /**
     * @param string[] $addedTrackingCodes
     */
    private function addTrackers(
        array $addedTrackingCodes,
        string $transactionId,
        string $orderDeliveryId,
        string $carrier,
        string $salesChannelId,
    ): void {
        if (!$addedTrackingCodes) {
            return;
        }

        $trackers = new TrackerCollection();
        foreach ($addedTrackingCodes as $trackingCode) {
            $trackers->add($this->createTracker($transactionId, $trackingCode, $carrier, Tracker::STATUS_SHIPPED));
        }

        $this->logger->info('Adding tracking codes for order delivery "{orderDeliveryId}"', [
            'orderDeliveryId' => $orderDeliveryId,
            'trackers' => \array_values($addedTrackingCodes),
        ]);

        $shipping = new Shipping();
        $shipping->setTrackers($trackers);
        $this->shippingResource->batch($shipping, $salesChannelId);
    }

    /**
     * @param string[] $removedTrackingCodes
     */
    private function removeTrackers(
        array $removedTrackingCodes,
        string $transactionId,
        string $orderDeliveryId,
        string $carrier,
        string $salesChannelId,
    ): void {
        if (!$removedTrackingCodes) {
            return;
        }

        foreach ($removedTrackingCodes as $trackingCode) {
            $tracker = $this->createTracker($transactionId, $trackingCode, $carrier, Tracker::STATUS_CANCELLED);

            $this->shippingResource->update($tracker, $salesChannelId);
        }

        $this->logger->info('Removed tracking codes for order delivery "{orderDeliveryId}"', [
            'orderDeliveryId' => $orderDeliveryId,
            'trackers' => \array_values($removedTrackingCodes),
        ]);
    }

    private function getPayPalTransactionId(string $orderDeliveryId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('order.deliveries.id', $orderDeliveryId));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        if ($transaction === null) {
            return null;
        }

        $customFields = $transaction->getCustomFields();
        if (!$customFields) {
            return null;
        }

        return $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID] ?? null;
    }

    private function fetchCarrier(string $orderDeliveryId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderDeliveries.id', $orderDeliveryId));
        $criteria->setLimit(1);

        $shippingMethod = $this->shippingMethodRepository->search($criteria, $context)->first();
        if ($shippingMethod === null) {
            return null;
        }

        $customFields = $shippingMethod->getTranslation('customFields') ?? [];
        if (!\is_array($customFields)) {
            return null;
        }

        return $customFields[SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER] ?? null;
    }

    private function fetchOrderSalesChannelId(string $orderDeliveryId, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orders.deliveries.id', $orderDeliveryId));
        $criteria->setLimit(1);

        $id = $this->salesChannelRepository->searchIds($criteria, $context)->firstId();
        if ($id === null) {
            throw OrderException::orderDeliveryNotFound($orderDeliveryId);
        }

        return $id;
    }

    private function createTracker(string $transactionId, string $trackingCode, string $carrier, string $status): Tracker
    {
        $tracking = new Tracker();
        $tracking->setTransactionId($transactionId);
        $tracking->setStatus($status);
        $tracking->setCarrier($carrier);
        $tracking->setTrackingNumber($trackingCode);

        return $tracking;
    }
}

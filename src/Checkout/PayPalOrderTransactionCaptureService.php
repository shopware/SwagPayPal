<?php

declare(strict_types=1);

namespace Swag\PayPal\Checkout;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\SwagPayPal;

class PayPalOrderTransactionCaptureService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionCaptureRepository;

    /**
     * @var OrderTransactionCaptureService
     */
    private $orderTransactionCaptureService;

    public function __construct(
        EntityRepositoryInterface $orderTransactionCaptureRepository,
        OrderTransactionCaptureService $orderTransactionCaptureService
    ) {
        $this->orderTransactionCaptureRepository = $orderTransactionCaptureRepository;
        $this->orderTransactionCaptureService = $orderTransactionCaptureService;
    }

    public function createOrderTransactionCaptureForFullAmount(string $orderTransactionId, Context $context): string
    {
        return $this->orderTransactionCaptureService->createOrderTransactionCaptureForFullAmount(
            $orderTransactionId,
            $context
        );
    }

    public function createOrderTransactionCaptureForCustomAmount(
        string $orderTransactionId,
        float $customCaptureAmount,
        Context $context
    ): string {
        return $this->orderTransactionCaptureService->createOrderTransactionCaptureForCustomAmount(
            $orderTransactionId,
            $customCaptureAmount,
            $context
        );
    }

    public function deleteOrderTransactionCapture(string $orderTransactionCaptureId, Context $context): void
    {
        $this->orderTransactionCaptureService->deleteOrderTransactionCapture($orderTransactionCaptureId, $context);
    }

    public function addPayPalResourceToOrderTransactionCapture(
        string $orderTransactionCaptureId,
        string $paypalResourceId,
        string $paypalResourceType,
        Context $context
    ): void {
        $data = [
            'id' => $orderTransactionCaptureId,
            'externalReference' => $paypalResourceId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CAPTURE_CUSTOM_FIELDS_PAYPAL_RESOURCE_TYPE => $paypalResourceType,
            ],
        ];
        $this->orderTransactionCaptureRepository->update([$data], $context);
    }
}

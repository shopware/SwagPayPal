<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Handler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\SwagPayPal;

abstract class AbstractPaymentHandler
{
    /**
     * @var PaymentResource
     */
    protected $paymentResource;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    public function __construct(PaymentResource $paymentResource, EntityRepositoryInterface $orderTransactionRepo)
    {
        $this->paymentResource = $paymentResource;
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    /**
     * @param Patch[] $patches
     *
     * @throws AsyncPaymentProcessException
     */
    protected function patchPayPalPayment(
        array $patches,
        string $paypalPaymentId,
        string $salesChannelId,
        string $orderTransactionId
    ): void {
        try {
            $this->paymentResource->patch($patches, $paypalPaymentId, $salesChannelId);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                'An error occurred during the communication with PayPal' . PHP_EOL . $e->getMessage()
            );
        }
    }

    protected function addPayPalTransactionId(
        AsyncPaymentTransactionStruct $transaction,
        string $paypalPaymentId,
        Context $context
    ): void {
        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                SwagPayPal::PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME => $paypalPaymentId,
            ],
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}

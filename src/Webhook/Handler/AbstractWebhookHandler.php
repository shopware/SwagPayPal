<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Payment;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Webhook\Exception\ParentPaymentNotFoundException;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\WebhookHandler;

#[Package('checkout')]
abstract class AbstractWebhookHandler implements WebhookHandler
{
    /**
     * @internal
     */
    public function __construct(
        protected readonly EntityRepository $orderTransactionRepository,
        protected readonly OrderTransactionStateHandler $orderTransactionStateHandler
    ) {}

    abstract public function getEventType(): string;

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    abstract public function invoke(PayPalApiStruct $webhook, Context $context): void;

    /**
     * @throws ParentPaymentNotFoundException
     * @throws WebhookOrderTransactionNotFoundException
     */
    protected function getOrderTransaction(WebhookV1 $webhook, Context $context): OrderTransactionEntity
    {
        $payPalTransactionId = $webhook->getResource()->getParentPayment();

        if ($payPalTransactionId === null) {
            throw new ParentPaymentNotFoundException($this->getEventType());
        }

        $criteria = new Criteria();
        $criteria->addAssociation('order');
        $criteria->addFilter(
            new EqualsFilter(
                \sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID),
                $payPalTransactionId
            )
        );
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw new WebhookOrderTransactionNotFoundException(
                \sprintf('with the PayPal ID "%s"', $payPalTransactionId),
                $this->getEventType()
            );
        }

        return $orderTransaction;
    }

    protected function getOrderTransactionV2(Payment $resource, Context $context): OrderTransactionEntity
    {
        $customId = $resource->getCustomId() ?? '[]';
        $customIdArray = \json_decode($customId, true);
        if (!\is_array($customIdArray)) {
            $orderTransactionId = $customId;
        } else {
            $orderTransactionId = $customIdArray['orderTransactionId'];
        }

        if ($orderTransactionId === null) {
            throw new WebhookException($this->getEventType(), 'Given webhook resource data does not contain needed custom ID');
        }

        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociations(['order', 'stateMachineState']);
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw new WebhookOrderTransactionNotFoundException(
                \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId),
                $this->getEventType()
            );
        }

        return $orderTransaction;
    }

    protected function isChangeAllowed(OrderTransactionEntity $orderTransaction, string $invalidStatus): bool
    {
        $state = $orderTransaction->getStateMachineState();
        if ($state === null) {
            return true;
        }

        return $state->getTechnicalName() !== $invalidStatus;
    }
}

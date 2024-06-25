<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Vault;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\VaultablePaymentSourceInterface;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V3\Api\PaymentToken;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class VaultPaymentTokenCreated extends AbstractWebhookHandler
{
    /**
     * @internal
     *
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly VaultTokenService $vaultTokenService,
        private readonly AbstractPaymentTransactionStructFactory $paymentTransactionStructFactory,
        private readonly OrderResource $orderResource,
    ) {
        parent::__construct($orderTransactionRepository, $orderTransactionStateHandler);
    }

    public function getEventType(): string
    {
        return WebhookEventTypes::VAULT_PAYMENT_TOKEN_CREATED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $resource = $webhook->getResource();
        if (!$resource instanceof PaymentToken) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }

        $orderId = $resource->getMetadata()?->getOrderId();
        if (!$orderId) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.' . SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID, $resource->getId()));
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.subscription');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();
        $order = $orderTransaction?->getOrder();
        if (!$orderTransaction || !$order) {
            return;
        }

        $customerId = $order->getOrderCustomer()?->getCustomerId();
        if (!$customerId) {
            return;
        }

        $struct = $this->paymentTransactionStructFactory->sync($orderTransaction, $order);

        $paymentSource = $this->orderResource->get($orderId, $order->getSalesChannelId())->getPaymentSource()?->first(VaultablePaymentSourceInterface::class);
        if ($paymentSource === null) {
            return;
        }

        // just to be safe, overwrite the token from the webhook
        $vault = new Vault();
        $vault->assign([
            'id' => $resource->getId(),
            'customer' => [
                'id' => $resource->getCustomer()?->getId(),
            ],
        ]);
        $attributes = $paymentSource->getAttributes() ?? new Attributes();
        $attributes->setVault($vault);
        $paymentSource->setAttributes($attributes);

        $this->vaultTokenService->saveToken($struct, $paymentSource, $customerId, $context);
    }
}

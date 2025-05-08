<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\PayPalSDK\Contract\Struct\V2\Order\PaymentSource\VaultablePaymentSourceInterface;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\AbstractPaymentSource;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractPaymentMethodHandler extends AbstractPaymentHandler
{
    public const PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME = 'paypalOrderId';
    public const PAYPAL_REQUEST_PARAMETER_CANCEL = 'cancel';

    /**
     * @internal
     */
    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly StateMachineRegistry $stateMachineRegistry,
        private readonly OrderExecuteService $orderExecuteService,
        private readonly OrderPatchService $orderPatchService,
        private readonly TransactionDataService $transactionDataService,
        private readonly OrderResource $orderResource,
        private readonly VaultTokenService $vaultTokenService,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly AbstractOrderBuilder $orderBuilder,
    ) {
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return $type === PaymentHandlerType::RECURRING && $this->isVaultable();
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): ?RedirectResponse {
        $paypalOrderId = $request->request->getAlnum(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        [$orderTransaction, $order] = $this->fetchOrderTransaction($transaction->getOrderTransactionId(), $context);
        $existingVault = $this->isVaultable() ? $this->vaultTokenService->getAvailableToken($transaction, $orderTransaction, $order, $context) : null;
        if ($this->requirePreparedOrder() && !$paypalOrderId && !$existingVault) {
            throw CheckoutException::preparedOrderRequired(static::class);
        }

        $this->settingsValidationService->validate($order->getSalesChannelId());

        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transaction->getOrderTransactionId(),
                $this->getProgressTransactionState(),
                'stateId'
            ),
            $context
        );

        $response = null;
        if (!$paypalOrderId) {
            $paypalOrder = $this->orderBuilder->getOrder($transaction, $orderTransaction, $order, $context, $request);
            $response = $this->orderResource->create(
                $paypalOrder,
                $order->getSalesChannelId(),
                $this->resolvePartnerAttributionId($request),
                true,
                $transaction->getOrderTransactionId() . ($orderTransaction->getUpdatedAt()?->getTimestamp() ?: ''),
                $this->getMetaDataId($request),
            );
            $paypalOrderId = $response->getId();
        }

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransactionId(),
            $paypalOrderId,
            $this->resolvePartnerAttributionId($request),
            $order->getSalesChannelId(),
            $context,
        );

        if (!$response) {
            $this->orderPatchService->patchOrder(
                $order,
                $orderTransaction,
                $context,
                $paypalOrderId,
                PartnerAttributionId::PAYPAL_PPCP
            );
            $response = $this->orderResource->get($paypalOrderId, $order->getSalesChannelId());
        }

        if ($action = $this->resolveRedirect($response)) {
            return new RedirectResponse($action);
        }

        $this->executeOrder(
            $transaction,
            $response,
            $order,
            $orderTransaction,
            $context,
        );

        return null;
    }

    public function finalize(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
    ): void {
        [$orderTransaction, $order] = $this->fetchOrderTransaction($transaction->getOrderTransactionId(), $context);

        $paypalOrderId = $orderTransaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);
        if (!\is_string($paypalOrderId) || !$paypalOrderId) {
            throw CheckoutException::preparedOrderRequired(static::class);
        }

        if ($request->query->getBoolean(self::PAYPAL_REQUEST_PARAMETER_CANCEL)) {
            throw PaymentException::customerCanceled(
                $transaction->getOrderTransactionId(),
                'Customer canceled the payment on the PayPal page'
            );
        }

        $this->executeOrder(
            $transaction,
            $this->orderResource->get($paypalOrderId, $order->getSalesChannelId()),
            $order,
            $orderTransaction,
            $context,
        );
    }

    public function recurring(PaymentTransactionStruct $transaction, Context $context): void
    {
        if (!$this->isVaultable()) {
            parent::recurring($transaction, $context);

            return;
        }

        $subscription = $this->vaultTokenService->getSubscription($transaction);
        if (!$subscription) {
            throw PaymentException::recurringInterrupted($transaction->getOrderTransactionId(), 'Subscription not found');
        }

        [$orderTransaction, $order] = $this->fetchOrderTransaction($transaction->getOrderTransactionId(), $context);

        $this->settingsValidationService->validate($order->getSalesChannelId());
        $paypalOrder = $this->orderBuilder->getOrder($transaction, $orderTransaction, $order, $context, new Request());
        $response = $this->orderResource->create(
            $paypalOrder,
            $order->getSalesChannelId(),
            PartnerAttributionId::PAYPAL_PPCP,
            true,
            $transaction->getOrderTransactionId() . ($orderTransaction->getUpdatedAt()?->getTimestamp() ?: ''),
        );

        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransactionId(),
            $response->getId(),
            PartnerAttributionId::PAYPAL_PPCP,
            $order->getSalesChannelId(),
            $context,
        );

        $this->executeOrder(
            $transaction,
            $response,
            $order,
            $orderTransaction,
            $context,
            false,
        );
    }

    protected function executeOrder(PaymentTransactionStruct $transaction, Order $paypalOrder, OrderEntity $order, OrderTransactionEntity $orderTransaction, Context $context, bool $isUserPresent = true): Order
    {
        $paypalOrder = $this->orderExecuteService->captureOrAuthorizeOrder(
            $transaction->getOrderTransactionId(),
            $paypalOrder,
            $order->getSalesChannelId(),
            $context,
            PartnerAttributionId::PAYPAL_PPCP,
        );

        $this->transactionDataService->setResourceId($paypalOrder, $transaction->getOrderTransactionId(), $context);

        if (!$this->isVaultable()) {
            return $paypalOrder;
        }

        /** @var (VaultablePaymentSourceInterface&AbstractPaymentSource)|null $vaultableSource */
        $vaultableSource = $paypalOrder->getPaymentSource()?->first(VaultablePaymentSourceInterface::class);
        if (!$vaultableSource) {
            return $paypalOrder;
        }

        $customerId = $order->getOrderCustomer()?->getCustomerId();
        if (!$customerId) {
            return $paypalOrder;
        }

        $this->vaultTokenService->saveToken($transaction, $orderTransaction, $vaultableSource, $customerId, $context);

        return $paypalOrder;
    }

    /**
     * If this method returns true, the payment handler will:
     * - be available for recurring payments
     * - attempt to save the payment source as a vault token
     */
    abstract protected function isVaultable(): bool;

    /**
     * If no PayPal order ID is provided, the payment handler will fail if this method returns true.
     * Otherwise, it will attempt to create a new PayPal order via the OrderBuilder
     */
    abstract protected function requirePreparedOrder(): bool;

    protected function resolvePartnerAttributionId(Request $request): string
    {
        return PartnerAttributionId::PAYPAL_PPCP;
    }

    protected function getProgressTransactionState(): string
    {
        return StateMachineTransitionActions::ACTION_PROCESS_UNCONFIRMED;
    }

    protected function getMetaDataId(Request $request): ?string
    {
        return null;
    }

    protected function resolveRedirect(?Order $order): ?string
    {
        return $order?->getLinks()->getRelation(Link::RELATION_PAYER_ACTION)?->getHref();
    }

    /**
     * @return array{0: OrderTransactionEntity, 1: OrderEntity}
     */
    private function fetchOrderTransaction(string $transactionId, Context $context): array
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.salesChannel');

        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        \assert($transaction instanceof OrderTransactionEntity);

        $order = $transaction->getOrder();
        \assert($order instanceof OrderEntity);

        return [$transaction, $order];
    }
}

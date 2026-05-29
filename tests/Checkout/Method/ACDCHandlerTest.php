<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionsRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionCollection;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Common\LinkCollection;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card\AuthenticationResult;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Swag\PayPal\Checkout\Card\CardValidatorInterface;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCHandlerTest extends TestCase
{
    private ACDCHandler $handler;

    private OrderExecuteService&MockObject $orderExecuteService;

    private OrderPatchService&MockObject $orderPatchService;

    private TransactionDataService&MockObject $transactionDataService;

    private StateMachineRegistry&MockObject $stateMachineRegistry;

    private SettingsValidationService&MockObject $settingsValidationService;

    private CardValidatorInterface&MockObject $acdcValidator;

    private OrderResource&MockObject $orderResource;

    private VaultTokenService&MockObject $vaultTokenService;

    private ACDCOrderBuilder&MockObject $orderBuilder;

    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $orderTransactionRepository;

    protected function setUp(): void
    {
        $this->handler = new ACDCHandler(
            $this->settingsValidationService = $this->createMock(SettingsValidationService::class),
            $this->stateMachineRegistry = $this->createMock(StateMachineRegistry::class),
            $this->orderExecuteService = $this->createMock(OrderExecuteService::class),
            $this->orderPatchService = $this->createMock(OrderPatchService::class),
            $this->transactionDataService = $this->createMock(TransactionDataService::class),
            $this->orderResource = $this->createMock(OrderResource::class),
            $this->vaultTokenService = $this->createMock(VaultTokenService::class),
            $this->orderTransactionRepository = new StaticEntityRepository([], new OrderTransactionDefinition()),
            $this->orderBuilder = $this->createMock(ACDCOrderBuilder::class),
            $this->acdcValidator = $this->createMock(CardValidatorInterface::class),
        );
    }

    public function testPayWithExistingOrder(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $paypalOrder = $this->createOrderObject();
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);

        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getAvailableToken')
            ->with($paymentTransaction, static::isInstanceOf(OrderTransactionEntity::class), static::isInstanceOf(OrderEntity::class))
            ->willReturn(null);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setOrderId')
            ->with(
                'orderTransactionId',
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                $order->getSalesChannelId(),
                $context,
            );

        $this->orderPatchService
            ->expects($this->once())
            ->method('patchOrder')
            ->with(
                $order,
                $transaction,
                $context,
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP
            );

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypalOrderId', $order->getSalesChannelId())
            ->willReturn($paypalOrder);

        $this->stateMachineRegistry
            ->expects($this->once())
            ->method('transition')
            ->with(static::equalTo(new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transaction->getId(),
                StateMachineTransitionActions::ACTION_PROCESS_UNCONFIRMED,
                'stateId'
            )), $context);

        $this->settingsValidationService
            ->expects($this->once())
            ->method('validate')
            ->with($order->getSalesChannelId());

        $this->acdcValidator
            ->expects($this->once())
            ->method('validate')
            ->with($paypalOrder, $transaction, $context)
            ->willReturn(true);

        $this->handler->pay(
            new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            $paymentTransaction,
            $context,
            null,
        );
    }

    public function testPayWithoutExistingOrder(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $request = new Request();
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);
        $link = new Link();
        $link->setHref('payerAction');
        $link->setRel(Link::RELATION_PAYER_ACTION);
        $payPalOrder = $this->createOrderObject($link);

        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getAvailableToken')
            ->with($paymentTransaction, $transaction, $order, $context)
            ->willReturn(new VaultTokenEntity());

        $this->transactionDataService
            ->expects($this->once())
            ->method('setOrderId')
            ->with(
                $paymentTransaction->getOrderTransactionId(),
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                $order->getSalesChannelId(),
                $context
            );

        $this->orderPatchService
            ->expects($this->never())
            ->method('patchOrder');

        $this->stateMachineRegistry
            ->expects($this->once())
            ->method('transition')
            ->with(static::equalTo(new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $transaction->getId(),
                StateMachineTransitionActions::ACTION_PROCESS_UNCONFIRMED,
                'stateId'
            )), $context);

        $this->settingsValidationService
            ->expects($this->once())
            ->method('validate')
            ->with($order->getSalesChannelId());

        $this->orderBuilder
            ->expects($this->once())
            ->method('getOrder')
            ->with($paymentTransaction, $transaction, $order, $context, $request)
            ->willReturn($payPalOrder);

        $this->orderResource
            ->expects($this->once())
            ->method('create')
            ->with($payPalOrder)
            ->willReturn($payPalOrder);

        $response = $this->handler->pay(
            $request,
            $paymentTransaction,
            $context,
            null,
        );

        static::assertSame('payerAction', $response?->getTargetUrl());
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);

        $this->transactionDataService
            ->expects($this->never())
            ->method('setOrderId');

        $this->orderPatchService
            ->expects($this->never())
            ->method('patchOrder');

        $this->orderTransactionRepository->addSearch(
            new OrderTransactionCollection([$transaction])
        );

        $this->settingsValidationService
            ->expects($this->once())
            ->method('validate')
            ->with($order->getSalesChannelId())
            ->willThrowException(new PayPalSettingsInvalidException('clientId'));

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->handler->pay(
            new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            $paymentTransaction,
            $context,
            null,
        );
    }

    public function testPayWithoutValidOrderId(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();

        $this->transactionDataService
            ->expects($this->never())
            ->method('setOrderId');

        $this->orderPatchService
            ->expects($this->never())
            ->method('patchOrder');

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setOrder(new OrderEntity());
        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('PayPal Order ID does not exist in the request. The payment method ' . ACDCHandler::class . ' requires a prepared PayPal order.', '/') . '\z/');
        $this->handler->pay(new Request(), $paymentTransaction, $context, null);
    }

    public function testFinalizeInvalid3DSecure(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $paypalOrder = $this->createOrderObject();

        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);
        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->willReturn($paypalOrder);

        $this->acdcValidator
            ->expects($this->once())
            ->method('validate')
            ->with($paypalOrder, $transaction, $context)
            ->willReturn(false);

        $this->expectException(CardValidationFailedException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('Credit card validation failed, 3D secure was not validated.', '/') . '\z/');
        $this->handler->finalize(new Request(), $paymentTransaction, $context);
    }

    public function testFinalizeWithoutOrderId(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setOrder(new OrderEntity());
        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('PayPal Order ID does not exist in the request. The payment method ' . ACDCHandler::class . ' requires a prepared PayPal order.', '/') . '\z/');
        $this->handler->finalize(new Request([]), $paymentTransaction, $context);
    }

    public function testFinalizeValid3DSecure(): void
    {
        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $order->setOrderCustomer($orderCustomer);
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);
        $payPalOrder = $this->createOrderObject();

        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->willReturn($payPalOrder);

        $this->acdcValidator
            ->expects($this->once())
            ->method('validate')
            ->with($payPalOrder, $transaction, $context)
            ->willReturn(true);

        $this->orderExecuteService
            ->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $transaction->getId(),
                $payPalOrder,
                $order->getSalesChannelId(),
                $context,
                PartnerAttributionId::PAYPAL_PPCP
            )
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, $transaction->getId(), $context);

        $this->vaultTokenService
            ->expects($this->once())
            ->method('saveToken')
            ->with($paymentTransaction, $transaction, $payPalOrder->getPaymentSource()?->getCard(), $orderCustomer->getCustomerId(), $context);

        $this->handler->finalize(new Request(), $paymentTransaction, $context);
    }

    public function testFinalizeFallbackButton(): void
    {
        $paypalOrderId = 'paypalOrderId';

        $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', 'returnUrl');
        $context = Context::createDefaultContext();
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $order->setOrderCustomer($orderCustomer);
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypalOrderId',
        ]);
        $transaction->setOrder($order);
        $payPalOrder = $this->createOrderObject();
        $payPalOrder->getPaymentSource()?->setCard(null);
        $payPalOrder->getPaymentSource()?->setPaypal(new Paypal());

        $this->orderTransactionRepository->addSearch([$transaction]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->willReturn($payPalOrder);

        $this->acdcValidator
            ->expects($this->never())
            ->method('validate');

        $this->orderExecuteService
            ->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $transaction->getId(),
                $payPalOrder,
                $order->getSalesChannelId(),
                $context,
                PartnerAttributionId::PAYPAL_PPCP
            )
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, $transaction->getId(), $context);

        $this->vaultTokenService
            ->expects($this->once())
            ->method('saveToken')
            ->with($paymentTransaction, $transaction, $payPalOrder->getPaymentSource()?->getPaypal(), $orderCustomer->getCustomerId(), $context);

        $this->handler->finalize(new Request(), $paymentTransaction, $context);
    }

    public function testRecurring(): void
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $context = Context::createDefaultContext();

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $order = new OrderEntity();
        $order->setSalesChannelId('salesChannelId');
        $transaction->setOrder($order);
        $subscription = new SubscriptionEntity();
        $subscription->setId('subscriptionId');
        $subscription->setNextSchedule(new \DateTime());
        $subscriptions = new SubscriptionCollection([$subscription]);

        $payPalOrder = $this->createOrderObject();

        $this->orderTransactionRepository->addSearch([$transaction]);

        /** @deprecated tag:v11.0.0 - Condition will always be true */
        if (\class_exists(SubscriptionsRecurringDataStruct::class)) {
            $recurring = new SubscriptionsRecurringDataStruct($subscriptions);
            $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', null, $recurring);

            $this->vaultTokenService
                ->expects($this->once())
                ->method('getSubscriptions')
                ->with($paymentTransaction)
                ->willReturn($subscriptions);
        } else {
            $recurring = new SubscriptionRecurringDataStruct($subscription);
            $paymentTransaction = new PaymentTransactionStruct('orderTransactionId', null, $recurring);

            $this->vaultTokenService
                ->expects($this->once())
                ->method('getSubscriptions')
                ->with($paymentTransaction)
                ->willReturn($subscriptions);
        }

        $this->transactionDataService
            ->expects($this->once())
            ->method('setOrderId')
            ->with(
                'orderTransactionId',
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                $order->getSalesChannelId(),
                $context
            );
        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, 'orderTransactionId', $context);

        $this->orderPatchService
            ->expects($this->never())
            ->method('patchOrder');

        $this->settingsValidationService
            ->expects($this->once())
            ->method('validate')
            ->with($order->getSalesChannelId());

        $this->orderBuilder
            ->expects($this->once())
            ->method('getOrder')
            ->with($paymentTransaction, $transaction, $order, $context, new Request())
            ->willReturn($payPalOrder);

        $this->orderResource
            ->expects($this->once())
            ->method('create')
            ->with($payPalOrder)
            ->willReturn($payPalOrder);

        $this->orderExecuteService
            ->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $transaction->getId(),
                $payPalOrder,
                $order->getSalesChannelId(),
                $context,
                PartnerAttributionId::PAYPAL_PPCP
            )
            ->willReturn($payPalOrder);

        $this->handler->recurring(
            $paymentTransaction,
            $context,
        );
    }

    public function testRecurringWithoutSubscription(): void
    {
        $paymentTransaction = new PaymentTransactionStruct(
            'orderTransactionId',
            null,
        );

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getSubscriptions')
            ->with($paymentTransaction)
            ->willReturn(null);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The recurring capture process was interrupted due to the following error:
Subscription not found', '/') . '\z/');
        $this->handler->recurring(
            $paymentTransaction,
            Context::createDefaultContext(),
        );
    }

    private function createOrderObject(?Link $link = null): Order
    {
        $order = new Order();
        $order->setId('paypalOrderId');
        $order->setLinks(new LinkCollection($link ? [$link] : []));

        $card = new Card();
        $card->setAuthenticationResult(new AuthenticationResult());
        $paymentSource = new PaymentSource();
        $paymentSource->setCard($card);

        $order->setPaymentSource($paymentSource);

        return $order;
    }
}

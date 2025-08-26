<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Venmo;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class VenmoHandlerTest extends AbstractTestSyncAPMHandler
{
    private VenmoHandler $handler;

    private OrderPatchService&MockObject $orderPatchService;

    private TransactionDataService&MockObject $transactionDataService;

    private SettingsValidationService&MockObject $settingsValidationService;

    private OrderResource&MockObject $orderResource;

    private VaultTokenService&MockObject $vaultTokenService;

    private VenmoOrderBuilder&MockObject $orderBuilder;

    private OrderExecuteService&MockObject $orderExecuteService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new VenmoHandler(
            $this->settingsValidationService = $this->createMock(SettingsValidationService::class),
            $this->stateMachineRegistry,
            $this->orderExecuteService = $this->createMock(OrderExecuteService::class),
            $this->orderPatchService = $this->createMock(OrderPatchService::class),
            $this->transactionDataService = $this->createMock(TransactionDataService::class),
            $this->orderResource = $this->createMock(OrderResource::class),
            $this->vaultTokenService = $this->createMock(VaultTokenService::class),
            $this->orderTransactionRepo,
            $this->orderBuilder = $this->createMock(VenmoOrderBuilder::class),
        );
    }

    public function testRecurring(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $context = Context::createDefaultContext();

        $subscription = new SubscriptionEntity();
        $subscription->setId('subscriptionId');
        $subscription->setNextSchedule(new \DateTime());
        $paymentTransaction = new PaymentTransactionStruct(
            $this->getTransactionId($context, $this->getContainer()),
            null,
            new SubscriptionRecurringDataStruct($subscription),
        );

        $paypalOrder = $this->createOrderObject();

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getSubscription')
            ->with($paymentTransaction)
            ->willReturn($subscription);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setOrderId')
            ->with(
                $paymentTransaction->getOrderTransactionId(),
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                TestDefaults::SALES_CHANNEL,
                $context
            );
        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($paypalOrder, $paymentTransaction->getOrderTransactionId(), $context);

        $this->orderPatchService
            ->expects($this->never())
            ->method('patchOrder');

        $this->settingsValidationService
            ->expects($this->once())
            ->method('validate')
            ->with(TestDefaults::SALES_CHANNEL);

        $this->orderBuilder
            ->expects($this->once())
            ->method('getOrder')
            ->with($paymentTransaction, static::isInstanceOf(OrderTransactionEntity::class), static::isInstanceOf(OrderEntity::class), $context, new Request())
            ->willReturn($paypalOrder);

        $this->orderResource
            ->expects($this->once())
            ->method('create')
            ->with($paypalOrder)
            ->willReturn($paypalOrder);

        $this->orderExecuteService
            ->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->with($paymentTransaction->getOrderTransactionId(), $paypalOrder)
            ->willReturn($paypalOrder);

        $this->handler->recurring(
            $paymentTransaction,
            $context,
        );
    }

    public function testRecurringWithoutSubscription(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $paymentTransaction = new PaymentTransactionStruct(
            'orderTransactionId',
            null,
            null,
        );

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getSubscription')
            ->with($paymentTransaction)
            ->willReturn(null);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The recurring capture process was interrupted due to the following error:
Subscription not found');
        $this->handler->recurring(
            $paymentTransaction,
            $salesChannelContext->getContext(),
        );
    }

    protected function getPaymentHandlerClassName(): string
    {
        return VenmoHandler::class;
    }

    protected function createPaymentHandler(array $settings = []): AbstractPaymentMethodHandler
    {
        $systemConfig = self::createSystemConfigServiceMock($settings);
        $orderResource = new OrderResource(self::orderGateway(), new ApiContextFactoryMock());
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();

        return new VenmoHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->stateMachineRegistry,
            new OrderExecuteService(
                $orderResource,
                $orderTransactionStateHandler,
                new OrderNumberPatchBuilder(),
                $logger
            ),
            new OrderPatchService(
                $systemConfig,
                new PurchaseUnitPatchBuilder(
                    new PurchaseUnitProvider(
                        new AmountProvider(new PriceFormatter()),
                        new AddressProvider(),
                        new CustomIdProviderMock(),
                        $systemConfig
                    ),
                    new ItemListProvider(
                        new PriceFormatter(),
                        $this->createMock(EventDispatcherInterface::class),
                        new NullLogger(),
                    ),
                ),
                $orderResource,
            ),
            new TransactionDataService(
                $this->orderTransactionRepo,
                new CredentialsUtil($systemConfig),
            ),
            $orderResource,
            new VaultTokenService(
                $this->createMock(EntityRepository::class),
                $this->createMock(EntityRepository::class),
                null,
            ),
            $this->orderTransactionRepo,
            $this->createMock(VenmoOrderBuilder::class),
        );
    }

    private function createOrderObject(): Order
    {
        $order = new Order();
        $order->setId('paypalOrderId');

        $venmo = new Venmo();
        $paymentSource = new PaymentSource();
        $paymentSource->setVenmo($venmo);

        $order->setPaymentSource($paymentSource);

        return $order;
    }
}

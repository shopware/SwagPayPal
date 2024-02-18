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
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\Payment\Method\AbstractSyncAPMHandler;
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
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Venmo;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class VenmoHandlerTest extends AbstractSyncAPMHandlerTest
{
    private VenmoHandler $handler;

    private OrderPatchService&MockObject $orderPatchService;

    private TransactionDataService&MockObject $transactionDataService;

    private SettingsValidationService&MockObject $settingsValidationService;

    private OrderResource&MockObject $orderResource;

    private VaultTokenService&MockObject $vaultTokenService;

    private VenmoOrderBuilder&MockObject $orderBuilder;

    private OrderConverter&MockObject $orderConverter;

    protected function setUp(): void
    {
        $this->handler = new VenmoHandler(
            $this->settingsValidationService = $this->createMock(SettingsValidationService::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderExecuteService::class),
            $this->orderPatchService = $this->createMock(OrderPatchService::class),
            $this->transactionDataService = $this->createMock(TransactionDataService::class),
            new NullLogger(),
            $this->orderResource = $this->createMock(OrderResource::class),
            $this->vaultTokenService = $this->createMock(VaultTokenService::class),
            $this->orderBuilder = $this->createMock(VenmoOrderBuilder::class),
            $this->orderConverter = $this->createMock(OrderConverter::class),
        );

        parent::setUp();
    }

    public function testRecurring(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        $salesChannelContext = Generator::createSalesChannelContext();

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $order = new OrderEntity();
        $subscription = new SubscriptionEntity();
        $subscription->setId('subscriptionId');
        $subscription->setNextSchedule(new \DateTime());
        $paymentTransaction = new RecurringPaymentTransactionStruct(
            $transaction,
            $order,
            new SubscriptionRecurringDataStruct($subscription),
        );

        $paypalOrder = $this->createOrderObject();

        $this->vaultTokenService
            ->expects(static::once())
            ->method('getSubscription')
            ->with($paymentTransaction)
            ->willReturn($subscription);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setOrderId')
            ->with(
                $paymentTransaction->getOrderTransaction()->getId(),
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );
        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($paypalOrder, $paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->orderPatchService
            ->expects(static::never())
            ->method('patchOrder');

        $this->settingsValidationService
            ->expects(static::once())
            ->method('validate')
            ->with($salesChannelContext->getSalesChannelId());

        $this->orderBuilder
            ->expects(static::once())
            ->method('getOrder')
            ->with($paymentTransaction, $salesChannelContext, new RequestDataBag())
            ->willReturn($paypalOrder);

        $this->orderResource
            ->expects(static::once())
            ->method('create')
            ->with($paypalOrder)
            ->willReturn($paypalOrder);

        $this->orderConverter
            ->expects(static::once())
            ->method('assembleSalesChannelContext')
            ->with($order, $salesChannelContext->getContext())
            ->willReturn($salesChannelContext);

        $this->handler->captureRecurring(
            $paymentTransaction,
            $salesChannelContext->getContext(),
        );
    }

    public function testRecurringWithoutSubscription(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();

        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $paymentTransaction = new RecurringPaymentTransactionStruct(
            $transaction,
            new OrderEntity(),
            null,
        );

        $this->vaultTokenService
            ->expects(static::once())
            ->method('getSubscription')
            ->with($paymentTransaction)
            ->willReturn(null);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The recurring capture process was interrupted due to the following error:
Subscription not found');
        $this->handler->captureRecurring(
            $paymentTransaction,
            $salesChannelContext->getContext(),
        );
    }

    protected function getPaymentHandlerClassName(): string
    {
        return VenmoHandler::class;
    }

    protected function createPaymentHandler(array $settings = []): AbstractSyncAPMHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $this->clientFactory = new PayPalClientFactoryMock(new NullLogger());
        $orderResource = new OrderResource($this->clientFactory);
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();

        return new VenmoHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $orderTransactionStateHandler,
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
            $logger,
            $orderResource,
            new VaultTokenService(
                $this->createMock(EntityRepository::class),
                $this->createMock(EntityRepository::class),
                null,
            ),
            $this->createMock(VenmoOrderBuilder::class),
            $this->createMock(OrderConverter::class),
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

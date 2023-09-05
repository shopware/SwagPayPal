<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\MessageQueue\Handler\InventoryUpdateHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\StockSubscriber;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\OrderLineItemRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosInventoryRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;
use Symfony\Component\Messenger\MessageBus;

/**
 * @internal
 */
class StockSubscriberTest extends TestCase
{
    use BasicTestDataBehaviour;
    use KernelTestBehaviour;
    use SalesChannelTrait;

    public function testStateChanged(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $event = $this->createStateMachineTransitionEvent($order->getId(), $context);

            $stockSubscriber->stateChanged($event);
        });
    }

    public function testStateChangedWithoutPosSalesChannel(): void
    {
        $event = $this->createStateMachineTransitionEvent(Uuid::randomHex(), Context::createDefaultContext());

        /** @var EntityRepository|MockObject $orderLineItemRepo */
        $orderLineItemRepo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $orderLineItemRepo->expects(static::never())->method('search');
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $stockSubscriber = new StockSubscriber(
            $orderLineItemRepo,
            new MessageBusMock(),
            $salesChannelRepository
        );

        $stockSubscriber->stateChanged($event);
    }

    public function testLineItemWritten(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $event = $this->createEntityWrittenEvent($order, $context);

            $stockSubscriber->lineItemWritten($event);
        });
    }

    public function testLineItemWrittenWithoutPosSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $order = $this->createOrder($context);
        $event = $this->createEntityWrittenEvent($order, $context);

        $this->createStockSubscriber()->lineItemWritten($event);
    }

    public function testOrderPlaced(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $event = $this->createCheckoutOrderPlacedEvent($context, $order);

            $stockSubscriber->orderPlaced($event);
        });
    }

    public function testOrderPlacedWithoutPosSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $order = $this->createOrder($context);
        $event = $this->createCheckoutOrderPlacedEvent($context, $order);

        $this->createStockSubscriber()->orderPlaced($event);
    }

    private function process(callable $callback, bool $shouldWork = true): void
    {
        $context = Context::createDefaultContext();

        $inventoryResource = new InventoryResource(new PosClientFactoryMock());
        $inventoryRepository = new PosInventoryRepoMock();
        $productRepository = new ProductRepoMock();
        $salesChannelProductRepository = new SalesChannelProductRepoMock();
        $salesChannel = $this->getSalesChannel($context);
        $salesChannelRepository = new SalesChannelRepoMock();
        $salesChannelRepository->getCollection()->clear();
        $salesChannelRepository->addMockEntity($salesChannel);

        $inventoryContextFactory = new InventoryContextFactory(
            $inventoryResource,
            new UuidConverter(),
            $inventoryRepository
        );

        $messageBus = new MessageBusMock();
        $messageDispatcher = new MessageDispatcher($messageBus, $this->createMock(Connection::class));

        $inventorySyncManager = new InventorySyncManager(
            $messageDispatcher,
            new ProductSelection(
                $salesChannelProductRepository,
                $this->createMock(ProductStreamBuilder::class),
                $this->getContainer()->get(SalesChannelContextFactory::class),
            ),
            $salesChannelProductRepository,
            $inventoryContextFactory
        );

        $runService = new RunServiceMock(
            new RunRepoMock(),
            new RunLogRepoMock(),
            $this->createMock(Connection::class),
            new Logger('test')
        );

        $inventoryUpdateHandler = new InventoryUpdateHandler(
            $runService,
            $salesChannelRepository,
            $inventorySyncManager,
            $messageDispatcher
        );

        $orderLineItemRepository = new OrderLineItemRepoMock();

        $stockSubscriber = new StockSubscriber(
            $orderLineItemRepository,
            $messageBus,
            $salesChannelRepository
        );

        /*
         * A - unchanged
         * B - increased stock
         * C - decreased stock
         */
        $productA = $productRepository->createMockEntity('productA', 2, 1, ConstantsForTesting::PRODUCT_A_ID);
        $salesChannelProductRepository->addMockEntity($productA);
        $productB = $productRepository->createMockEntity('productB', 2, 2, ConstantsForTesting::PRODUCT_B_ID);
        $salesChannelProductRepository->addMockEntity($productB);
        $productC = $productRepository->createMockEntity('productC', 2, 0, ConstantsForTesting::PRODUCT_C_ID);
        $salesChannelProductRepository->addMockEntity($productC);

        $inventoryRepository->createMockEntity($productA, TestDefaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productB, TestDefaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productC, TestDefaults::SALES_CHANNEL, 1);

        $order = $this->createOrder($context);
        $lineItems = $order->getLineItems();
        static::assertNotNull($lineItems);

        /** @var OrderLineItemCollection $repoCollection */
        $repoCollection = $orderLineItemRepository->getCollection();
        $repoCollection->merge($lineItems);

        $callback($stockSubscriber, $order, $context);
        $messageBus->execute([$inventoryUpdateHandler]);

        $inventoryMessageCreated = false;
        foreach ($messageBus->getEnvelopes() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof InventorySyncMessage) {
                $inventoryMessageCreated = true;
                static::assertEqualsCanonicalizing(
                    [
                        ConstantsForTesting::PRODUCT_A_ID,
                        ConstantsForTesting::PRODUCT_B_ID,
                        ConstantsForTesting::PRODUCT_C_ID,
                    ],
                    $message->getInventoryContext()->getProductIds()
                );
            }
        }

        static::assertSame($shouldWork, $inventoryMessageCreated);
    }

    private function createStockSubscriber(): StockSubscriber
    {
        /** @var MessageBus|MockObject $messageBus */
        $messageBus = $this->getMockBuilder(MessageBus::class)->disableOriginalConstructor()->getMock();
        $messageBus->expects(static::never())->method('dispatch');
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        return new StockSubscriber(
            new OrderLineItemRepoMock(),
            $messageBus,
            $salesChannelRepository
        );
    }

    private function createEntityWrittenEvent(OrderEntity $order, Context $context): EntityWrittenEvent
    {
        return new EntityWrittenEvent(OrderLineItemDefinition::ENTITY_NAME, [
            new EntityWriteResult(
                Uuid::randomHex(),
                [
                    'orderId' => $order->getId(),
                    'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                    'referencedId' => ConstantsForTesting::PRODUCT_A_ID,
                    'quantity' => 1,
                ],
                OrderLineItemDefinition::ENTITY_NAME,
                EntityWriteResult::OPERATION_INSERT
            ),
            new EntityWriteResult(
                Uuid::randomHex(),
                [],
                OrderLineItemDefinition::ENTITY_NAME,
                EntityWriteResult::OPERATION_UPDATE,
                null,
                new ChangeSet(
                    [
                        'order_id' => $order->getId(),
                        'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                        'referenced_id' => ConstantsForTesting::PRODUCT_C_ID,
                        'quantity' => 2,
                    ],
                    [
                        'order_id' => $order->getId(),
                        'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                        'referenced_id' => ConstantsForTesting::PRODUCT_C_ID,
                        'quantity' => 1,
                    ],
                    false
                )
            ),
            new EntityWriteResult(
                Uuid::randomHex(),
                [],
                OrderLineItemDefinition::ENTITY_NAME,
                EntityWriteResult::OPERATION_UPDATE,
                null,
                new ChangeSet(
                    [
                        'order_id' => $order->getId(),
                        'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                        'referenced_id' => ConstantsForTesting::PRODUCT_B_ID,
                    ],
                    [
                        'order_id' => $order->getId(),
                        'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                        'referenced_id' => ConstantsForTesting::PRODUCT_C_ID,
                    ],
                    false
                )
            ),
        ], $context);
    }

    private function createOrder(Context $context): OrderEntity
    {
        $order = new OrderEntity();
        $order->assign([
            'id' => Uuid::randomHex(),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $this->getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE),
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'orderDateTime' => '2019-04-01 08:36:43.267',
        ]);

        $productIds = [
            ConstantsForTesting::PRODUCT_A_ID,
            ConstantsForTesting::PRODUCT_B_ID,
            ConstantsForTesting::PRODUCT_C_ID,
        ];

        $lineItems = new OrderLineItemCollection();
        foreach ($productIds as $productId) {
            $lineItem = new OrderLineItemEntity();
            $lineItem->assign([
                'id' => Uuid::randomHex(),
                'versionId' => Uuid::randomHex(),
                'identifier' => 'test',
                'quantity' => 1,
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'label' => 'test',
                'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'priceDefinition' => new QuantityPriceDefinition(10, new TaxRuleCollection(), 2),
                'priority' => 100,
                'good' => true,
                'referencedId' => $productId,
            ]);
            $lineItems->add($lineItem);
        }

        $order->setLineItems($lineItems);

        return $order;
    }

    private function createStateMachineTransitionEvent(string $orderId, Context $context): StateMachineTransitionEvent
    {
        $from = new StateMachineStateEntity();
        $from->setTechnicalName(OrderStates::STATE_OPEN);
        $to = new StateMachineStateEntity();
        $to->setTechnicalName(OrderStates::STATE_CANCELLED);

        return new StateMachineTransitionEvent(OrderDefinition::ENTITY_NAME, $orderId, $from, $to, $context);
    }

    private function createCheckoutOrderPlacedEvent(Context $context, OrderEntity $order): CheckoutOrderPlacedEvent
    {
        return new CheckoutOrderPlacedEvent(
            $context,
            $order,
            TestDefaults::SALES_CHANNEL
        );
    }
}

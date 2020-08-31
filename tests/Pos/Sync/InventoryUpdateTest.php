<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync;

use Monolog\Logger;
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
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\MessageQueue\Handler\InventoryUpdateHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Run\RunService;
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

class InventoryUpdateTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;
    use BasicTestDataBehaviour;

    public function testStateChanged(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $from = new StateMachineStateEntity();
            $from->setTechnicalName(OrderStates::STATE_OPEN);
            $to = new StateMachineStateEntity();
            $to->setTechnicalName(OrderStates::STATE_CANCELLED);

            $event = new StateMachineTransitionEvent(OrderDefinition::ENTITY_NAME, $order->getId(), $from, $to, $context);

            $stockSubscriber->stateChanged($event);
        });
    }

    public function testLineItemWritten(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $event = new EntityWrittenEvent(OrderLineItemDefinition::ENTITY_NAME, [
                new EntityWriteResult(
                    Uuid::randomHex(),
                    [],
                    OrderLineItemDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_INSERT,
                    null,
                    new ChangeSet(
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_C_ID, 'quantity' => 1],
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_C_ID, 'quantity' => 1],
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
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_C_ID, 'quantity' => 2],
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_C_ID, 'quantity' => 1],
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
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_B_ID],
                        ['order_id' => $order->getId(), 'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, 'referenced_id' => ConstantsForTesting::PRODUCT_C_ID],
                        false
                    )
                ),
            ], $context);

            $stockSubscriber->lineItemWritten($event);
        });
    }

    public function testOrderPlaced(): void
    {
        $this->process(function (StockSubscriber $stockSubscriber, OrderEntity $order, Context $context): void {
            $event = new CheckoutOrderPlacedEvent(
                $context,
                $order,
                Defaults::SALES_CHANNEL
            );

            $stockSubscriber->orderPlaced($event);
        });
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

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get('Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory');

        $messageBus = new MessageBusMock();

        $inventorySyncManager = new InventorySyncManager(
            $messageBus,
            new ProductSelection(
                $salesChannelProductRepository,
                $this->createMock(ProductStreamBuilder::class),
                $salesChannelContextFactory
            ),
            $salesChannelProductRepository,
            $inventoryContextFactory
        );

        $runService = new RunService(
            new RunRepoMock(),
            new RunLogRepoMock(),
            new Logger('test')
        );

        $inventoryUpdateHandler = new InventoryUpdateHandler(
            $runService,
            $salesChannelRepository,
            $inventorySyncManager,
            $messageBus
        );

        $orderLineItemRepository = new OrderLineItemRepoMock();

        $stockSubscriber = new StockSubscriber(
            $orderLineItemRepository,
            $messageBus
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

        $inventoryRepository->createMockEntity($productA, Defaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productB, Defaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productC, Defaults::SALES_CHANNEL, 1);

        $orderId = Uuid::randomHex();

        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        static::assertNotNull($stateMachineRegistry);

        $order = new OrderEntity();
        $order->assign([
            'id' => $orderId,
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $stateMachineRegistry->getInitialState(OrderStates::STATE_MACHINE, $context)->getId(),
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'orderDateTime' => '2019-04-01 08:36:43.267',
        ]);

        $lineItems = new OrderLineItemCollection();
        foreach ([ConstantsForTesting::PRODUCT_A_ID, ConstantsForTesting::PRODUCT_B_ID, ConstantsForTesting::PRODUCT_C_ID] as $productId) {
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
        $orderLineItemRepository->getCollection()->merge($lineItems);

        $callback($stockSubscriber, $order, $context);
        $messageBus->execute([$inventoryUpdateHandler]);

        $inventoryMessageCreated = false;
        $syncManagerMessageCreated = false;
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
            if ($message instanceof SyncManagerMessage) {
                $syncManagerMessageCreated = true;
            }
        }

        if ($shouldWork) {
            static::assertTrue($inventoryMessageCreated);
            static::assertTrue($syncManagerMessageCreated);
        } else {
            static::assertFalse($inventoryMessageCreated);
            static::assertFalse($syncManagerMessageCreated);
        }
    }
}

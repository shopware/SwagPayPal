<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping\MessageQueue;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessage;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessageHandler;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\Tracker;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingInformationMessageHandler::class)]
class ShippingInformationMessageHandlerTest extends TestCase
{
    private EntityRepository&MockObject $orderDeliveryRepository;

    private OrderResource&MockObject $orderResource;

    private LoggerInterface&MockObject $logger;

    private ShippingInformationMessageHandler $handler;

    protected function setUp(): void
    {
        $this->orderDeliveryRepository = $this->createMock(EntityRepository::class);
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ShippingInformationMessageHandler(
            $this->orderDeliveryRepository,
            $this->orderResource,
            $this->logger,
        );
    }

    #[DataProvider('provideInvokeData')]
    public function testInvoke(
        ?OrderDeliveryEntity $orderDelivery,
        Order $payPalOrder,
        array $addedTrackers,
        array $removedTrackers,
        bool $isDataComplete,
    ): void {
        $carrier = $orderDelivery?->getShippingMethod()?->getCustomFieldsValue(SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER) ?: Tracker::CARRIER_OTHER;
        $hasCapture = $payPalOrder->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->first() !== null;
        $hasLineItems = $orderDelivery?->getOrder()?->getLineItems() !== null;

        $this->orderDeliveryRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($orderDelivery));

        $this->orderResource
            ->expects(static::exactly((int) $isDataComplete))
            ->method('get')
            ->willReturn($payPalOrder);

        $this->orderResource
            ->expects(self::exactlyIf($hasCapture && $isDataComplete, \count($addedTrackers)))
            ->method('addTracker')
            ->with(
                static::callback(static function (Tracker $tracker) use (&$addedTrackers, &$carrier, $hasLineItems): bool {
                    static::assertSame(\array_shift($addedTrackers), $tracker->getTrackingNumber());
                    static::assertSame('paypal-capture-id', $tracker->getCaptureId());
                    static::assertSame($carrier, $tracker->getCarrier());
                    static::assertCount((int) $hasLineItems, $tracker->getItems());

                    return true;
                }),
                'paypal-order-id',
                'sales-channel-id',
                'paypal-partner-id'
            );

        $this->orderResource
            ->expects(self::exactlyIf($hasCapture && $isDataComplete, \count($removedTrackers)))
            ->method('removeTracker')
            ->with(
                static::callback(static function (Tracker $tracker) use (&$removedTrackers, &$carrier, $hasLineItems): bool {
                    static::assertSame(\array_shift($removedTrackers), $tracker->getTrackingNumber());
                    static::assertSame('paypal-capture-id', $tracker->getCaptureId());
                    static::assertSame($carrier, $tracker->getCarrier());
                    static::assertCount((int) $hasLineItems, $tracker->getItems());

                    if ($tracker->getCarrier() === Tracker::CARRIER_OTHER) {
                        static::assertSame('shipping-method-name', $tracker->getCarrierNameOther());
                    }

                    return true;
                }),
                'paypal-order-id',
                'sales-channel-id',
                'paypal-partner-id'
            );

        $this->logger
            ->expects(self::exactlyIf($hasCapture && $isDataComplete, ((int) !empty($addedTrackers)) + ((int) !empty($removedTrackers))))
            ->method('info');

        ($this->handler)(new ShippingInformationMessage('order-delivery-id'));
    }

    public static function provideInvokeData(): \Generator
    {
        yield 'complete' => [
            self::createOrderDelivery(self::createOrder(), trackingCodes: ['code-a']),
            self::createPayPalOrder(),
            ['code-a'],
            [],
            true,
        ];

        yield 'complete, missing line-items' => [
            self::createOrderDelivery(self::createOrder(hasLineItems: false), trackingCodes: ['code-a']),
            self::createPayPalOrder(),
            ['code-a'],
            [],
            true,
        ];

        yield 'complete, with empty string tracking code' => [
            self::createOrderDelivery(self::createOrder(hasLineItems: false), trackingCodes: ['']),
            self::createPayPalOrder(),
            [],
            [],
            true,
        ];

        yield 'complete, missing shipping method' => [
            self::createOrderDelivery(self::createOrder(), hasShippingMethod: false),
            self::createPayPalOrder(),
            [],
            [],
            true,
        ];

        yield 'complete, changed carrier' => [
            self::createOrderDelivery(self::createOrder(), carrier: 'carrier'),
            self::createPayPalOrder(),
            [],
            [],
            true,
        ];

        yield 'complete, OTHER carrier' => [
            self::createOrderDelivery(self::createOrder(), carrier: 'OTHER'),
            self::createPayPalOrder(),
            [],
            [],
            true,
        ];

        yield 'complete, added extra long tracking code' => [
            self::createOrderDelivery(self::createOrder(), trackingCodes: [
                'this-is-a-tracking-code-longer-than-64-characters-so-it-will-be-trimmed',
            ]),
            self::createPayPalOrder(),
            ['this-is-a-tracking-code-longer-than-64-characters-so-it-will-be-'],
            [],
            true,
        ];

        yield 'complete, extra long, existing tracking code' => [
            self::createOrderDelivery(self::createOrder(), trackingCodes: [
                'this-is-a-tracking-code-longer-than-64-characters-so-it-will-be-trimmed',
            ]),
            self::createPayPalOrder(trackingCodes: [
                'this-is-a-tracking-code-longer-than-64-characters-so-it-will-be-',
            ]),
            [],
            [],
            true,
        ];

        yield 'complete, added and removed tracking codes' => [
            self::createOrderDelivery(self::createOrder(), trackingCodes: [
                'code-a',
                'code-c',
            ]),
            self::createPayPalOrder(trackingCodes: [
                'code-a',
                'code-b',
            ]),
            ['code-c'],
            ['code-b'],
            true,
        ];

        yield 'incomplete, missing order transaction' => [
            self::createOrderDelivery(self::createOrder(hasTransaction: false)),
            self::createPayPalOrder(),
            [],
            [],
            false,
        ];

        yield 'incomplete, missing paypal transaction custom fields' => [
            self::createOrderDelivery(self::createOrder(isPayPalTransaction: false)),
            self::createPayPalOrder(),
            [],
            [],
            false,
        ];

        yield 'incomplete, missing order' => [
            self::createOrderDelivery(null),
            self::createPayPalOrder(),
            [],
            [],
            false,
        ];

        yield 'incomplete, missing paypal capture id' => [
            self::createOrderDelivery(self::createOrder()),
            self::createPayPalOrder(hasCapture: false),
            [],
            [],
            true,
        ];
    }

    public function testRetryOnInvalidParameterException(): void
    {
        $orderDelivery = self::createOrderDelivery(self::createOrder(), carrier: 'invalid-carrier', trackingCodes: ['code-a']);
        $payPalOrder = self::createPayPalOrder(trackingCodes: ['code-b']);
        $payPalException = new PayPalApiException('', '(/carrier)', issue: PayPalApiException::ERROR_CODE_INVALID_PARAMETER_VALUE);

        $this->orderDeliveryRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($orderDelivery));

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($payPalOrder);

        $matcher = static::exactly(2);
        $this->orderResource
            ->expects($matcher)
            ->method('addTracker')
            ->with(
                static::callback(static function (Tracker $tracker) use (&$matcher): bool {
                    match ($matcher->numberOfInvocations()) {
                        1 => static::assertSame('invalid-carrier', $tracker->getCarrier()),
                        2 => static::assertEquals(['OTHER', 'invalid-carrier'], [$tracker->getCarrier(), $tracker->getCarrierNameOther()]),
                        default => static::fail('Exceeded expected number of invocations'),
                    };

                    return true;
                }),
                'paypal-order-id',
                'sales-channel-id',
                'paypal-partner-id'
            )
            ->willReturnOnConsecutiveCalls(
                static::throwException($payPalException),
                $payPalOrder,
            );

        $this->orderResource
            ->expects(static::once())
            ->method('removeTracker')
            ->with(
                static::callback(static function (Tracker $tracker): bool {
                    static::assertSame('OTHER', $tracker->getCarrier());

                    return true;
                }),
                'paypal-order-id',
                'sales-channel-id',
                'paypal-partner-id'
            );

        $this->logger
            ->expects(static::once())
            ->method('error');

        ($this->handler)(new ShippingInformationMessage('order-delivery-id'));
    }

    public function testNoRetryOnOtherExceptions(): void
    {
        $orderDelivery = self::createOrderDelivery(self::createOrder(), carrier: 'invalid-carrier', trackingCodes: ['code-a']);
        $payPalOrder = self::createPayPalOrder();
        $payPalException = new PayPalApiException('', '(/carrier)', issue: PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND);

        $this->orderDeliveryRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($orderDelivery));

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($payPalOrder);

        $this->orderResource
            ->expects(static::once())
            ->method('addTracker')
            ->with(
                static::callback(static function (Tracker $tracker): bool {
                    static::assertSame('invalid-carrier', $tracker->getCarrier());

                    return true;
                }),
                'paypal-order-id',
                'sales-channel-id',
                'paypal-partner-id'
            )
            ->willThrowException($payPalException);

        $this->orderResource
            ->expects(static::never())
            ->method('removeTracker');

        $this->logger
            ->expects(static::never())
            ->method('error');

        static::expectExceptionObject($payPalException);

        ($this->handler)(new ShippingInformationMessage('order-delivery-id'));
    }

    public function testInvalidPayPalOrderIdException(): void
    {
        $orderDelivery = self::createOrderDelivery(self::createOrder(), trackingCodes: ['code-a']);
        $payPalException = new PayPalApiException('', 'NOT FOUND', issue: PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND);

        $this->orderDeliveryRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(self::createSearchResult($orderDelivery));

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willThrowException($payPalException);

        $this->orderResource
            ->expects(static::never())
            ->method('removeTracker');

        $this->orderResource
            ->expects(static::never())
            ->method('addTracker');

        $this->logger
            ->expects(static::once())
            ->method('warning');

        ($this->handler)(new ShippingInformationMessage('order-delivery-id'));
    }

    private static function createPayPalOrder(
        bool $hasCapture = true,
        array $trackingCodes = [],
    ): Order {
        $trackers = \array_map(
            static fn (string $code) => (new Order\PurchaseUnit\Shipping\Tracker())->assign([
                'id' => 'ID-' . $code,
                'status' => 'SHIPPED',
            ]),
            $trackingCodes
        );

        $shipping = new Order\PurchaseUnit\Shipping();
        $shipping->setTrackers(new Order\PurchaseUnit\Shipping\TrackerCollection($trackers));

        $purchaseUnit = new Order\PurchaseUnit();
        $purchaseUnit->setShipping($shipping);

        $order = new Order();
        $order->getPurchaseUnits()->add($purchaseUnit);

        if ($hasCapture) {
            $capture = new Order\PurchaseUnit\Payments\Capture();
            $capture->setId('paypal-capture-id');

            $payments = new Order\PurchaseUnit\Payments();
            $payments->setCaptures(new Order\PurchaseUnit\Payments\CaptureCollection([$capture]));

            $purchaseUnit->setPayments($payments);
        }

        return $order;
    }

    private static function createOrderDelivery(
        ?OrderEntity $order,
        bool $hasShippingMethod = true,
        ?string $carrier = null,
        array $trackingCodes = [],
    ): OrderDeliveryEntity {
        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('order-delivery-id');

        if ($order !== null) {
            $orderDelivery->setOrder($order);
        }

        if ($hasShippingMethod) {
            $shippingMethod = new ShippingMethodEntity();
            $shippingMethod->setId('shipping-method-id');
            $shippingMethod->setTranslated(['name' => 'shipping-method-name']);
            $shippingMethod->setCustomFields([
                SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER => $carrier,
            ]);

            $orderDelivery->setShippingMethod($shippingMethod);
        }

        $orderDelivery->setTrackingCodes($trackingCodes);

        return $orderDelivery;
    }

    private static function createOrder(
        bool $hasLineItems = true,
        bool $hasTransaction = true,
        bool $isPayPalTransaction = true,
    ): OrderEntity {
        $order = new OrderEntity();
        $order->setId('order-id');
        $order->setSalesChannelId('sales-channel-id');

        if ($hasTransaction) {
            $orderTransaction = new OrderTransactionEntity();
            $orderTransaction->setId('order-transaction-id');

            if ($isPayPalTransaction) {
                $orderTransaction->setCustomFields([
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => 'paypal-partner-id',
                    SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => 'paypal-resource-id',
                ]);
            }

            $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));
        }

        if ($hasLineItems) {
            $orderLineItem1 = new OrderLineItemEntity();
            $orderLineItem1->setId('order-line-item-id-1');
            $orderLineItem1->setParentId('order-line-item-parent-id-1');
            $orderLineItem1->setLabel('order-line-item-label-1');
            $orderLineItem1->setQuantity(1);
            $orderLineItem1->setGood(true);

            $orderLineItem2 = new OrderLineItemEntity();
            $orderLineItem2->setId('order-line-item-id-2');
            $orderLineItem2->setParentId('order-line-item-parent-id-2');
            $orderLineItem2->setLabel('order-line-item-label-2');
            $orderLineItem2->setQuantity(1);
            $orderLineItem2->setGood(false);

            $orderLineItem3 = new OrderLineItemEntity();
            $orderLineItem3->setId('order-line-item-id-3');
            $orderLineItem3->setLabel('order-line-item-label-3');
            $orderLineItem3->setQuantity(1);
            $orderLineItem3->setGood(true);

            $order->setLineItems(new OrderLineItemCollection([$orderLineItem1, $orderLineItem2, $orderLineItem3]));
        }

        return $order;
    }

    private static function createSearchResult(?OrderDeliveryEntity $orderDelivery): EntitySearchResult
    {
        return new EntitySearchResult(
            OrderDeliveryDefinition::ENTITY_NAME,
            $orderDelivery ? 0 : 1,
            new OrderDeliveryCollection($orderDelivery ? [$orderDelivery] : []),
            null,
            new Criteria(),
            Context::createDefaultContext(),
        );
    }

    private static function exactlyIf(bool $condition, int $count): InvokedCount
    {
        return $condition ? static::exactly($count) : static::never();
    }
}

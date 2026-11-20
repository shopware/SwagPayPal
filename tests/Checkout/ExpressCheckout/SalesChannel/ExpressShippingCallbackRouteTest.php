<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnitCollection;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressShippingCallbackException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressShippingCallbackRoute::class)]
class ExpressShippingCallbackRouteTest extends TestCase
{
    use PayPalLogTrait;

    private MockObject&ExpressShippingCallbackService $service;

    private ExpressShippingCallbackRoute $route;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ExpressShippingCallbackService::class);
        $this->route = new ExpressShippingCallbackRoute(
            $this->service,
            $this->createPayPalLogger(),
        );
    }

    public function testHandleCallback(): void
    {
        $payload = [
            'id' => 'paypal-order-id',
            'shipping_address' => ['country_code' => 'DE'],
            'shipping_option' => [
                'id' => 'shipping-method-id',
                'label' => 'test-method',
            ],
            'purchase_units' => [['reference_id' => 'default']],
        ];

        $callback = (new OrderShippingCallback())->assign($payload);
        $request = new Request(request: $payload);
        $salesChannelContext = Generator::generateSalesChannelContext();
        $order = new Order();
        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setReferenceId('default');
        $order->unset('intent');
        $order->setId('paypal-order-id');
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        $this->service
            ->expects($this->once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willReturn($order);

        $response = $this->route->handleCallback($request, $salesChannelContext);
        $responsePayload = \json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertSame(['id', 'purchase_units'], \array_keys($responsePayload));
        static::assertSame('paypal-order-id', $responsePayload['id']);
        static::assertSame('default', $responsePayload['purchase_units'][0]['reference_id']);

        static::assertSame([], $this->getPayPalLogRecords());
    }

    /**
     * @param array<mixed> $payload
     */
    #[DataProvider('handleCallbackInvalidPayloadDataProvider')]
    public function testHandleCallbackInvalidPayload(array $payload): void
    {
        $request = new Request(request: $payload);
        $salesChannelContext = Generator::generateSalesChannelContext();

        $this->service
            ->expects($this->never())
            ->method('recalculateCart');

        $response = $this->route->handleCallback($request, $salesChannelContext);
        static::assertEquals(\json_encode(['error' => 'Invalid payload']), $response->getContent());

        $record = $this->assertPayPalLogged(LogCode::EXPRESS_CALLBACK_INVALID, Level::Warning, message: 'Rejected a shipping callback with an invalid payload');
        static::assertArrayNotHasKey('callback', $record->context);
    }

    public static function handleCallbackInvalidPayloadDataProvider(): \Generator
    {
        yield 'missing purchase_unit' => [[
            'id' => 'paypal-order-id',
            'shipping_address' => ['country_code' => 'DE'],
        ]];

        yield 'missing shipping_address' => [[
            'id' => 'paypal-order-id',
            'purchase_units' => [['reference_id' => 'default']],
        ]];

        yield 'missing id' => [[
            'purchase_units' => [['reference_id' => 'default']],
            'shipping_address' => ['country_code' => 'DE'],
        ]];
    }

    public function testHandleCallbackThrowsRandomException(): void
    {
        $payload = [
            'id' => 'paypal-order-id',
            'shipping_address' => ['country_code' => 'DE'],
            'shipping_option' => [
                'id' => 'shipping-method-id',
                'label' => 'test-method',
            ],
            'purchase_units' => [['reference_id' => 'default']],
        ];

        $callback = (new OrderShippingCallback())->assign($payload);
        $request = new Request(request: $payload);
        $salesChannelContext = Generator::generateSalesChannelContext();

        $expection = new \RuntimeException('test');

        $this->service
            ->expects($this->once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willThrowException($expection);

        static::expectExceptionObject($expection);

        try {
            $this->route->handleCallback($request, $salesChannelContext);
        } finally {
            $record = $this->assertPayPalLogged(
                LogCode::EXPRESS_CALLBACK_FAILED,
                Level::Error,
                [LogKey::PAYPAL_ORDER_ID => 'paypal-order-id'],
                'Could not answer the shipping callback for PayPal order {paypalOrderId}, PayPal shows the buyer an error',
            );
            static::assertSame($expection::class, $record->context[LogKey::EXCEPTION]['class'] ?? null);
            static::assertSame($expection->getMessage(), $record->context[LogKey::EXCEPTION]['message'] ?? null);
        }
    }

    public function testHandleCallbackThrowsShippingCallbackException(): void
    {
        $payload = [
            'id' => 'paypal-order-id',
            'shipping_address' => ['country_code' => 'DE'],
            'shipping_option' => [
                'id' => 'shipping-method-id',
                'label' => 'test-method',
            ],
            'purchase_units' => [['reference_id' => 'default']],
        ];

        $callback = (new OrderShippingCallback())->assign($payload);
        $request = new Request(request: $payload);
        $salesChannelContext = Generator::generateSalesChannelContext();

        $expection = ExpressShippingCallbackException::addressError($callback);

        $this->service
            ->expects($this->once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willThrowException($expection);

        $response = $this->route->handleCallback($request, $salesChannelContext);
        static::assertSame($expection->intoCallbackResponse()->getContent(), $response->getContent());

        $this->assertPayPalLogged(
            LogCode::EXPRESS_CALLBACK_SHIPPING_UNAVAILABLE,
            Level::Notice,
            [LogKey::PAYPAL_ORDER_ID => 'paypal-order-id', LogKey::COUNTRY_CODE => 'DE'],
            'Could not offer shipping for PayPal order {paypalOrderId}: {errorCode}',
        );
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressShippingCallbackException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\OrderShippingCallback;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @covers \Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute
 */
#[Package('checkout')]
class ExpressShippingCallbackRouteTest extends TestCase
{
    private MockObject&ExpressShippingCallbackService $service;

    private TestHandler $logger;

    private ExpressShippingCallbackRoute $route;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ExpressShippingCallbackService::class);
        $this->logger = new TestHandler();
        $this->route = new ExpressShippingCallbackRoute(
            $this->service,
            new Logger('test', [$this->logger]),
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
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->service
            ->expects(static::once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willReturn(new Order());

        $response = $this->route->handleCallback($request, $salesChannelContext);
        static::assertEquals(\json_encode(new Order()), $response->getContent());

        static::assertCount(1, $this->logger->getRecords());
        static::assertTrue($this->logger->hasDebug(['message' => 'Shipping callback received']));
    }

    /**
     * @dataProvider handleCallbackInvalidPayloadDataProvider
     *
     * @param array<mixed> $payload
     */
    public function testHandleCallbackInvalidPayload(array $payload): void
    {
        $request = new Request(request: $payload);
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->service
            ->expects(static::never())
            ->method('recalculateCart');

        $response = $this->route->handleCallback($request, $salesChannelContext);
        static::assertEquals(\json_encode(['error' => 'Invalid payload']), $response->getContent());

        static::assertCount(2, $this->logger->getRecords());
        static::assertTrue($this->logger->hasDebug(['message' => 'Shipping callback received']));
        static::assertTrue($this->logger->hasError(['message' => 'Shipping callback: Invalid payload']));
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
        $salesChannelContext = Generator::createSalesChannelContext();

        $expection = new \RuntimeException('test');

        $this->service
            ->expects(static::once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willThrowException($expection);

        static::expectExceptionObject($expection);

        try {
            $this->route->handleCallback($request, $salesChannelContext);
        } finally {
            static::assertCount(2, $this->logger->getRecords());
            static::assertTrue($this->logger->hasDebug(['message' => 'Shipping callback received']));
            static::assertTrue($this->logger->hasError(['message' => 'Shipping callback failed']));
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
        $salesChannelContext = Generator::createSalesChannelContext();

        $expection = ExpressShippingCallbackException::addressError($callback);

        $this->service
            ->expects(static::once())
            ->method('recalculateCart')
            ->with(static::equalTo($callback), $salesChannelContext)
            ->willThrowException($expection);

        $response = $this->route->handleCallback($request, $salesChannelContext);
        static::assertSame($expection->intoCallbackResponse()->getContent(), $response->getContent());

        static::assertCount(2, $this->logger->getRecords());
        static::assertTrue($this->logger->hasDebug(['message' => 'Shipping callback received']));
        static::assertTrue($this->logger->hasError(['message' => 'Shipping callback failed']));
    }
}

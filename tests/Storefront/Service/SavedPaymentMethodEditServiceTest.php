<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Storefront\Service\SavedPaymentMethodEditService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SavedPaymentMethodEditService::class)]
class SavedPaymentMethodEditServiceTest extends TestCase
{
    private OrderResource&MockObject $orderResource;

    private RequestStack $requestStack;

    private Session $session;

    private SalesChannelContext $salesChannelContext;

    private SavedPaymentMethodEditService $service;

    protected function setUp(): void
    {
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->session = new Session(new MockArraySessionStorage());

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack = new RequestStack([$request]);

        $this->salesChannelContext = Generator::generateSalesChannelContext(token: 'context-token');
        $this->service = new SavedPaymentMethodEditService($this->requestStack, $this->orderResource);
    }

    public function testRememberApprovedOrder(): void
    {
        $this->orderResource
            ->method('get')
            ->with('paypal-order-id', $this->salesChannelContext->getSalesChannelId())
            ->willReturn($this->createPayPalOrder(ConstantsV2::ORDER_APPROVED));

        static::assertTrue($this->service->remember($this->salesChannelContext, null, 'paypal-order-id'));
        static::assertSame(['context-token' => 'paypal-order-id'], $this->session->get(SavedPaymentMethodEditService::SESSION_KEY));
        static::assertSame('paypal-order-id', $this->service->getApprovedOrderId($this->salesChannelContext, null));
    }

    public function testRememberIsScopedToOrder(): void
    {
        $this->orderResource
            ->method('get')
            ->willReturn($this->createPayPalOrder(ConstantsV2::ORDER_APPROVED));

        static::assertTrue($this->service->remember($this->salesChannelContext, 'order-id', 'paypal-order-id'));
        static::assertSame('paypal-order-id', $this->service->getApprovedOrderId($this->salesChannelContext, 'order-id'));
        static::assertNull($this->service->getApprovedOrderId($this->salesChannelContext, 'other-order-id'));
        static::assertNull($this->service->getApprovedOrderId($this->salesChannelContext, null));
    }

    public function testRememberRejectsOrderNotApproved(): void
    {
        $this->orderResource
            ->method('get')
            ->willReturn($this->createPayPalOrder(ConstantsV2::ORDER_CREATED));

        static::assertFalse($this->service->remember($this->salesChannelContext, null, 'paypal-order-id'));
        static::assertNull($this->session->get(SavedPaymentMethodEditService::SESSION_KEY));
    }

    public function testRememberRejectsUnknownOrder(): void
    {
        $this->orderResource
            ->method('get')
            ->willThrowException(new PayPalApiException('RESOURCE_NOT_FOUND', 'The specified resource does not exist.', 404));

        static::assertFalse($this->service->remember($this->salesChannelContext, null, 'paypal-order-id'));
        static::assertNull($this->session->get(SavedPaymentMethodEditService::SESSION_KEY));
    }

    public function testRememberWithoutSession(): void
    {
        $this->orderResource->expects($this->never())->method('get');

        $service = new SavedPaymentMethodEditService(new RequestStack([new Request()]), $this->orderResource);

        static::assertFalse($service->remember($this->salesChannelContext, null, 'paypal-order-id'));
        static::assertNull($service->getApprovedOrderId($this->salesChannelContext, null));
    }

    public function testGetApprovedOrderIdForgetsOrderNoLongerApproved(): void
    {
        $this->session->set(SavedPaymentMethodEditService::SESSION_KEY, [
            'context-token' => 'paypal-order-id',
            'order-id' => 'other-paypal-order-id',
        ]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id')
            ->willReturn($this->createPayPalOrder(ConstantsV2::ORDER_COMPLETED));

        static::assertNull($this->service->getApprovedOrderId($this->salesChannelContext, null));
        static::assertNull($this->service->getApprovedOrderId($this->salesChannelContext, null));
        static::assertSame(['order-id' => 'other-paypal-order-id'], $this->session->get(SavedPaymentMethodEditService::SESSION_KEY));
    }

    public function testGetApprovedOrderIdWithoutEdit(): void
    {
        $this->orderResource->expects($this->never())->method('get');

        static::assertNull($this->service->getApprovedOrderId($this->salesChannelContext, null));
    }

    public function testForgetAll(): void
    {
        $this->session->set(SavedPaymentMethodEditService::SESSION_KEY, ['context-token' => 'paypal-order-id']);

        $this->service->forgetAll();

        static::assertFalse($this->session->has(SavedPaymentMethodEditService::SESSION_KEY));
    }

    private function createPayPalOrder(string $status): Order
    {
        return (new Order())->assign(['id' => 'paypal-order-id', 'status' => $status]);
    }
}

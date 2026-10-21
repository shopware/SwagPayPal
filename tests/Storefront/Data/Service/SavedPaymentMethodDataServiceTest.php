<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\SavedPaymentMethodDataService;
use Swag\PayPal\Storefront\Service\SavedPaymentMethodEditService;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SavedPaymentMethodDataService::class)]
class SavedPaymentMethodDataServiceTest extends TestCase
{
    use ServicesTrait;

    private SystemConfigServiceMock $systemConfigService;

    private SavedPaymentMethodEditService&MockObject $savedPaymentMethodEditService;

    private SavedPaymentMethodDataService $service;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->createSystemConfigServiceMock([
            Settings::SDK_V6_ENABLED => true,
        ]);
        $this->savedPaymentMethodEditService = $this->createMock(SavedPaymentMethodEditService::class);

        $paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $paymentMethodDataRegistry
            ->method('getPaymentMethod')
            ->with(PayPalMethodData::class)
            ->willReturn(new PayPalMethodData($this->createMock(Container::class)));
        $paymentMethodDataRegistry
            ->method('getEntityIdFromData')
            ->willReturn('paypal-payment-method-id');

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnArgument(0);

        $this->service = new SavedPaymentMethodDataService(
            $paymentMethodDataRegistry,
            $this->createMock(LocaleCodeProvider::class),
            $router,
            $this->systemConfigService,
            $this->createMock(CredentialsUtilInterface::class),
            $this->savedPaymentMethodEditService,
        );

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('EUR');

        $this->salesChannelContext = Generator::generateSalesChannelContext(currency: $currency);
        $this->salesChannelContext->getPaymentMethod()->setHandlerIdentifier(PayPalPaymentHandler::class);
    }

    public function testBuildCheckoutDataForCart(): void
    {
        $this->savedPaymentMethodEditService
            ->expects($this->once())
            ->method('getApprovedOrderId')
            ->with($this->salesChannelContext, null)
            ->willReturn('paypal-order-id');

        $data = $this->service->buildCheckoutData($this->salesChannelContext, new Cart(Uuid::randomHex()));

        static::assertNotNull($data);
        static::assertSame('frontend.paypal.saved_payment_method.edit', $data->getEditUrl());
        static::assertSame('paypal-order-id', $data->getApprovedOrderId());
        static::assertSame('frontend.paypal.create_order', $data->getCreateOrderUrl());
        static::assertSame('frontend.paypal.client_token', $data->getClientTokenUrl());
        static::assertSame('paypal-payment-method-id', $data->getPaymentMethodId());
        static::assertNull($data->getOrderId());
    }

    public function testBuildCheckoutDataForOrder(): void
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());

        $this->savedPaymentMethodEditService
            ->expects($this->once())
            ->method('getApprovedOrderId')
            ->with($this->salesChannelContext, $order->getId())
            ->willReturn(null);

        $data = $this->service->buildCheckoutData($this->salesChannelContext, null, $order);

        static::assertNotNull($data);
        static::assertSame($order->getId(), $data->getOrderId());
        static::assertNull($data->getApprovedOrderId());
    }

    public function testBuildCheckoutDataWithOtherPaymentMethod(): void
    {
        $this->salesChannelContext->getPaymentMethod()->setHandlerIdentifier(ACDCHandler::class);
        $this->savedPaymentMethodEditService->expects($this->never())->method('getApprovedOrderId');

        static::assertNull($this->service->buildCheckoutData($this->salesChannelContext, new Cart(Uuid::randomHex())));
    }

    public function testBuildCheckoutDataWithoutSdkV6(): void
    {
        $this->systemConfigService->set(Settings::SDK_V6_ENABLED, false);
        $this->savedPaymentMethodEditService->expects($this->never())->method('getApprovedOrderId');

        static::assertNull($this->service->buildCheckoutData($this->salesChannelContext, new Cart(Uuid::randomHex())));
    }

    public function testBuildCheckoutDataForExpressCheckout(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $cart->addExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID, new ArrayStruct());

        $this->savedPaymentMethodEditService->expects($this->never())->method('getApprovedOrderId');

        static::assertNull($this->service->buildCheckoutData($this->salesChannelContext, $cart));
    }

    public function testBuildCheckoutDataForSubscription(): void
    {
        $this->salesChannelContext->addExtension('subscription', new ArrayStruct());
        $this->savedPaymentMethodEditService->expects($this->never())->method('getApprovedOrderId');

        static::assertNull($this->service->buildCheckoutData($this->salesChannelContext, new Cart(Uuid::randomHex())));
    }

    public function testBuildCheckoutDataForInitialSubscriptionOrder(): void
    {
        $subscription = new ArrayEntity();
        $subscription->setUniqueIdentifier(Uuid::randomHex());

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->addExtension('initialSubscriptions', new EntityCollection([$subscription]));

        $this->savedPaymentMethodEditService->expects($this->never())->method('getApprovedOrderId');

        static::assertNull($this->service->buildCheckoutData($this->salesChannelContext, null, $order));
    }

    public function testBuildCheckoutDataForOrderWithoutInitialSubscriptions(): void
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->addExtension('initialSubscriptions', new EntityCollection());

        static::assertNotNull($this->service->buildCheckoutData($this->salesChannelContext, null, $order));
    }

    public function testGetMethodDataClass(): void
    {
        static::assertSame(PayPalMethodData::class, $this->service->getMethodDataClass());
    }
}

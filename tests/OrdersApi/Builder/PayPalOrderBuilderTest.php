<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderFromCartAddsAppSwitchContext(): void
    {
        $this->systemConfig->set(Settings::SPB_APP_SWITCH_ENABLED, true);

        $order = $this->getBuilder()->getOrderFromCart(
            $this->createCart(''),
            $this->createSalesChannelContext(),
            $this->createAppSwitchRequestData(),
        );

        $mobileWebContext = $order
            ->getPaymentSource()
            ?->getPaypal()
            ?->getExperienceContext()
            ?->getAppSwitchContext()
            ?->getMobileWeb();

        static::assertNotNull($mobileWebContext);
        static::assertSame('Mozilla/5.0 App Switch Test', $mobileWebContext->getBuyerUserAgent());
        static::assertSame('AUTO', $mobileWebContext->getReturnFlow());
    }

    public function testGetOrderFromCartSkipsAppSwitchContextWhenDisabled(): void
    {
        $order = $this->getBuilder()->getOrderFromCart(
            $this->createCart(''),
            $this->createSalesChannelContext(),
            $this->createAppSwitchRequestData(),
        );

        static::assertNull($order->getPaymentSource()?->getPaypal()?->getExperienceContext()?->getAppSwitchContext());
    }

    public function testGetOrderFromCartSkipsAppSwitchContextForVaulting(): void
    {
        $this->systemConfig->set(Settings::SPB_APP_SWITCH_ENABLED, true);
        $this->vaultTokenService
            ->expects($this->once())
            ->method('shouldRequestVaulting')
            ->willReturn(true);
        $this->vaultTokenService
            ->expects($this->once())
            ->method('requestVaulting');

        $order = $this->getBuilder()->getOrderFromCart(
            $this->createCart(''),
            $this->createSalesChannelContext(),
            $this->createAppSwitchRequestData([VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );

        static::assertNull($order->getPaymentSource()?->getPaypal()?->getExperienceContext()?->getAppSwitchContext());
    }

    public function testGetOrderAddsAppSwitchContext(): void
    {
        $this->systemConfig->set(Settings::SPB_APP_SWITCH_ENABLED, true);
        $orderTransaction = $this->createOrderTransaction();

        $order = $this->getBuilder()->getOrder(
            new PaymentTransactionStruct($orderTransaction->getId()),
            $orderTransaction,
            $this->createOrder(),
            Context::createDefaultContext(),
            new Request([], [
                'product' => 'spb',
                CreateOrderRoute::PAYPAL_BUYER_USER_AGENT => 'Mozilla/5.0 Edit Order App Switch Test',
            ], [
                AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true,
            ]),
        );

        $mobileWebContext = $order
            ->getPaymentSource()
            ?->getPaypal()
            ?->getExperienceContext()
            ?->getAppSwitchContext()
            ?->getMobileWeb();

        static::assertNotNull($mobileWebContext);
        static::assertSame('Mozilla/5.0 Edit Order App Switch Test', $mobileWebContext->getBuyerUserAgent());
    }

    public function testGetOrderFromCartUsesRequestReturnUrls(): void
    {
        $order = $this->getBuilder()->getOrderFromCart(
            $this->createCart(''),
            $this->createSalesChannelContext(),
            new RequestDataBag([
                CreateOrderRoute::RETURN_URL => 'https://example.test/paypal/restore-context/token',
                CreateOrderRoute::CANCEL_URL => 'https://example.test/paypal/restore-context/token',
            ]),
        );

        $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();
        static::assertNotNull($experienceContext);

        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext->getReturnUrl());
        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext->getCancelUrl());
    }

    public function testGetOrderUsesRequestReturnUrls(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->getBuilder()->getOrder(
            new PaymentTransactionStruct($orderTransaction->getId()),
            $orderTransaction,
            $this->createOrder(),
            Context::createDefaultContext(),
            new Request([], [
                CreateOrderRoute::RETURN_URL => 'https://example.test/paypal/restore-context/token',
                CreateOrderRoute::CANCEL_URL => 'https://example.test/paypal/restore-context/token',
            ]),
        );

        $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();
        static::assertNotNull($experienceContext);

        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext->getReturnUrl());
        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext->getCancelUrl());
    }

    public function testGetOrderNoBillingAddress(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $order->assign(['billingAddress' => null]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The required association "billingAddress" is missing .', '/') . '\z/');
        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );
    }

    protected function getBuilder(): AbstractOrderBuilder
    {
        return new PayPalOrderBuilder(
            $this->systemConfig,
            $this->purchaseUnitProvider,
            new AddressProvider(),
            $this->localeCodeProvider,
            $this->itemListProvider,
            $this->vaultTokenService,
        );
    }

    protected function getPaymentSourceClass(): string
    {
        return Paypal::class;
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    private function createAppSwitchRequestData(array $additionalData = []): RequestDataBag
    {
        return new RequestDataBag([
            'product' => 'spb',
            CreateOrderRoute::RETURN_URL => 'https://example.test/paypal/restore-context/token',
            CreateOrderRoute::CANCEL_URL => 'https://example.test/paypal/restore-context/token',
            CreateOrderRoute::PAYPAL_BUYER_USER_AGENT => 'Mozilla/5.0 App Switch Test',
            ...$additionalData,
        ]);
    }
}

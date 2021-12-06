<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\CheckoutSubscriber;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Request;

class CheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    private CheckoutSubscriber $checkoutSubscriber;

    private PaymentMethodDataRegistry $methodDataRegistry;

    public function setUp(): void
    {
        /** @var PaymentMethodDataRegistry $paymentMethodDataRegistry */
        $paymentMethodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);
        $this->methodDataRegistry = $paymentMethodDataRegistry;

        $this->checkoutSubscriber = new CheckoutSubscriber(
            $paymentMethodDataRegistry,
            new NullLogger(),
            new CartPriceService()
        );
    }

    public function testEvents(): void
    {
        $events = CheckoutSubscriber::getSubscribedEvents();
        static::assertCount(1, $events);
    }

    public function testConfirmPageExistingCredentials(): void
    {
        $event = $this->getCheckoutConfirmPageEvent(Generator::createCart());
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(\count($this->methodDataRegistry->getPaymentMethods()), $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesNotRemoveWithEmptyCart(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->getLineItems()->remove('B');
        $cart->setPrice($this->getEmptyCartPrice());

        $event = $this->getCheckoutConfirmPageEvent($cart, false);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(\count($this->methodDataRegistry->getPaymentMethods()), $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesRemoveWithCartWithValueZero(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice($this->getEmptyCartPrice());

        $event = $this->getCheckoutConfirmPageEvent($cart);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesNotRemoveWithNormalCart(): void
    {
        $cart = Generator::createCart();

        $event = $this->getCheckoutConfirmPageEvent($cart, false);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(\count($this->methodDataRegistry->getPaymentMethods()), $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesNotRemoveWithNormalCartAndPayPalSelected(): void
    {
        $cart = Generator::createCart();

        $event = $this->getCheckoutConfirmPageEvent($cart);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(\count($this->methodDataRegistry->getPaymentMethods()), $event->getPage()->getPaymentMethods());
    }

    public function testOrderEditPageDoesRemoveWithCartWithValueZero(): void
    {
        $event = $this->getOrderEditPageEvent();
        $event->getPage()->getOrder()->setPrice($this->createCartPrice(0.0, 0.0, 0.0));
        $this->checkoutSubscriber->onEditOrderPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    private function getCheckoutConfirmPageEvent(Cart $cart, bool $payPalSelected = true): CheckoutConfirmPageLoadedEvent
    {
        $context = Context::createDefaultContext();
        $paymentMethods = $this->getPaymentMethods($context);

        $salesChannelContext = Generator::createSalesChannelContext(
            $context,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $payPalSelected ? $paymentMethods->filterByProperty('handlerIdentifier', PayPalPaymentHandler::class)->first() : null
        );

        $page = new CheckoutConfirmPage();
        $page->setPaymentMethods($paymentMethods);
        $page->setCart($cart);

        return new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
    }

    private function getOrderEditPageEvent(): AccountEditOrderPageLoadedEvent
    {
        $context = Context::createDefaultContext();

        $paymentMethods = $this->getPaymentMethods($context);

        $salesChannelContext = Generator::createSalesChannelContext(
            $context,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $paymentMethods->filterByProperty('handlerIdentifier', PayPalPaymentHandler::class)->first()
        );

        $page = new AccountEditOrderPage();
        $page->setPaymentMethods($paymentMethods);
        $order = new OrderEntity();
        $order->setPrice($this->createCartPrice(20.0, 20.0, 20.0));
        $page->setOrder($order);

        return new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
    }

    private function getPaymentMethods(Context $context): PaymentMethodCollection
    {
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->getContainer()->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(SwagPayPal::class, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('pluginId', $pluginId));

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $paymentMethodRepository->search($criteria, $context)->getEntities();

        static::assertCount(3, $paymentMethods);

        return $paymentMethods;
    }
}

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
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\CheckoutSubscriber;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

class CheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    private CheckoutSubscriber $checkoutSubscriber;

    private SystemConfigServiceMock $settings;

    public function setUp(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);

        $this->settings = $this->createDefaultSystemConfig();
        $this->checkoutSubscriber = new CheckoutSubscriber(
            new SettingsValidationService($this->settings, new NullLogger()),
            $paymentMethodUtil,
            new NullLogger(),
            new CartPriceService()
        );
    }

    public function testEvents(): void
    {
        $events = CheckoutSubscriber::getSubscribedEvents();
        static::assertCount(2, $events);
    }

    public function testConfirmPageInvalidCredentials(): void
    {
        $event = $this->getCheckoutConfirmPageEvent(Generator::createCart());
        $this->settings->delete(Settings::CLIENT_ID);
        $this->settings->delete(Settings::CLIENT_SECRET);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageExistingCredentials(): void
    {
        $event = $this->getCheckoutConfirmPageEvent(Generator::createCart());
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(1, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesRemoveWithCartWithValueZeroAndPayPalInactive(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice($this->getEmptyCartPrice());

        $event = $this->getCheckoutConfirmPageEvent($cart, false);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesNotRemoveWithEmptyCart(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->getLineItems()->remove('B');
        $cart->setPrice($this->getEmptyCartPrice());

        $event = $this->getCheckoutConfirmPageEvent($cart, false);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(1, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesRemoveWithCartWithValueZeroAndPayPalActive(): void
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

        static::assertCount(1, $event->getPage()->getPaymentMethods());
    }

    public function testConfirmPageDoesNotRemoveWithNormalCartAndPayPalSelected(): void
    {
        $cart = Generator::createCart();

        $event = $this->getCheckoutConfirmPageEvent($cart);
        $this->checkoutSubscriber->onConfirmPageLoaded($event);

        static::assertCount(1, $event->getPage()->getPaymentMethods());
    }

    public function testOrderEditPageInvalidCredentials(): void
    {
        $event = $this->getOrderEditPageEvent();
        $this->settings->delete(Settings::CLIENT_ID);
        $this->settings->delete(Settings::CLIENT_SECRET);
        $this->checkoutSubscriber->onEditOrderPageLoaded($event);

        static::assertCount(0, $event->getPage()->getPaymentMethods());
    }

    public function testOrderEditPageDoesRemoveWithCartWithValueZeroAndPayPalActive(): void
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
            $payPalSelected ? $paymentMethods->first() : null
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
            $paymentMethods->first()
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class));

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $paymentMethodRepository->search($criteria, $context)->getEntities();

        static::assertCount(1, $paymentMethods);

        return $paymentMethods;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksData;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksSubscriber;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PaymentMethodUtilMock;
use Symfony\Component\HttpFoundation\Request;

class SPBMarksSubscriberTest extends TestCase
{
    use ServicesTrait;

    private const TEST_CLIENT_ID = 'testClientId';

    public function testGetSubscribedEvents(): void
    {
        $events = SPBMarksSubscriber::getSubscribedEvents();

        static::assertCount(4, $events);
        static::assertSame('addMarksExtension', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('addMarksExtension', $events[AccountPaymentMethodPageLoadedEvent::class]);
        static::assertSame('addMarksExtension', $events[FooterPageletLoadedEvent::class]);
        static::assertSame('addMarksExtension', $events[CheckoutConfirmPageLoadedEvent::class]);
    }

    public function testOnAccountPaymentMethodPageLoadedPayPalNotInActiveSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(
            new PaymentMethodCollection([])
        );
        $subscriber->addMarksExtension($event);

        static::assertNull(
            $event->getPage()->getExtension(SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID)
        );
    }

    public function testOnAccountPaymentMethodPageLoadedNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createAccountEvent();
        $subscriber->addMarksExtension($event);

        static::assertNull(
            $event->getPage()->getExtension(SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID)
        );
    }

    public function testOnAccountPaymentMethodPageLoadedSPBNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createAccountEvent();
        $subscriber->addMarksExtension($event);

        static::assertNull(
            $event->getPage()->getExtension(SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID)
        );
    }

    public function testOnAccountPaymentMethodPageLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEvent();
        $subscriber->addMarksExtension($event);

        /** @var SPBMarksData|null $spbMarksExtension */
        $spbMarksExtension = $event->getPage()->getExtension(
            SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID
        );

        static::assertNotNull($spbMarksExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbMarksExtension->getClientId());
        static::assertSame(PaymentMethodUtilMock::PAYMENT_METHOD_ID, $spbMarksExtension->getPaymentMethodId());
        static::assertTrue($spbMarksExtension->getUseAlternativePaymentMethods());
    }

    public function testOnFooterPageletLoadedSPBNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createFooterEvent();
        $subscriber->addMarksExtension($event);

        static::assertNull(
            $event->getPagelet()->getExtension(SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID)
        );
    }

    public function testOnFooterPageletLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFooterEvent();
        $subscriber->addMarksExtension($event);

        /** @var SPBMarksData|null $spbMarksExtension */
        $spbMarksExtension = $event->getPagelet()->getExtension(
            SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID
        );

        static::assertNotNull($spbMarksExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbMarksExtension->getClientId());
        static::assertSame(PaymentMethodUtilMock::PAYMENT_METHOD_ID, $spbMarksExtension->getPaymentMethodId());
    }

    public function testOnCheckoutConfirmPageLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createCheckoutConfirmEvent();
        $subscriber->addMarksExtension($event);

        /** @var SPBMarksData|null $spbMarksExtension */
        $spbMarksExtension = $event->getPage()->getExtension(
            SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID
        );

        static::assertNotNull($spbMarksExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbMarksExtension->getClientId());
        static::assertSame(PaymentMethodUtilMock::PAYMENT_METHOD_ID, $spbMarksExtension->getPaymentMethodId());
        static::assertTrue($spbMarksExtension->getUseAlternativePaymentMethods());
    }

    public function testOnCheckoutConfirmPageLoadedSPBNotEnabledExpressCheckoutActive(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createCheckoutConfirmEvent();
        $event->getPage()->getCart()->addExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID, new ArrayStruct());
        $subscriber->addMarksExtension($event);

        static::assertNull(
            $event->getPage()->getExtension(SPBMarksSubscriber::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID)
        );
    }

    private function createSubscriber(
        bool $withSettings = true,
        bool $spbEnabled = true
    ): SPBMarksSubscriber {
        $settings = $this->createSystemConfigServiceMock($withSettings ? [
            Settings::CLIENT_ID => self::TEST_CLIENT_ID,
            Settings::CLIENT_SECRET => 'testClientSecret',
            Settings::SPB_CHECKOUT_ENABLED => $spbEnabled,
            Settings::MERCHANT_LOCATION => Settings::MERCHANT_LOCATION_OTHER,
        ] : []);

        return new SPBMarksSubscriber(
            new SettingsValidationService($settings, new NullLogger()),
            $settings,
            new PaymentMethodUtilMock(),
            new NullLogger()
        );
    }

    private function createAccountEvent(): AccountPaymentMethodPageLoadedEvent
    {
        $salesChannelContext = $this->createSalesChannelContext();

        return new AccountPaymentMethodPageLoadedEvent(
            new AccountPaymentMethodPage(),
            $salesChannelContext,
            new Request()
        );
    }

    private function createFooterEvent(): FooterPageletLoadedEvent
    {
        $salesChannelContext = $this->createSalesChannelContext();

        return new FooterPageletLoadedEvent(new FooterPagelet(null), $salesChannelContext, new Request());
    }

    private function createCheckoutConfirmEvent(): CheckoutConfirmPageLoadedEvent
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $paymentMethodCollection = $salesChannelContext->getSalesChannel()->getPaymentMethods();
        static::assertNotNull($paymentMethodCollection);
        $confirmPage = new CheckoutConfirmPage();
        $confirmPage->setPaymentMethods($paymentMethodCollection);
        $confirmPage->setShippingMethods(new ShippingMethodCollection());
        $confirmPage->setCart(new Cart('test-cart', 'test-token'));

        return new CheckoutConfirmPageLoadedEvent($confirmPage, $salesChannelContext, new Request());
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );

        $paypalPaymentMethod = new PaymentMethodEntity();
        $paypalPaymentMethod->setId(PaymentMethodUtilMock::PAYMENT_METHOD_ID);
        $salesChannelContext->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection([
            $paypalPaymentMethod,
        ]));

        return $salesChannelContext;
    }
}

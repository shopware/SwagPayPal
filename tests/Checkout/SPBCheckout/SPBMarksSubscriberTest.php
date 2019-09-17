<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksData;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksSubscriber;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\PaymentMethodUtilMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Symfony\Component\HttpFoundation\Request;

class SPBMarksSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    private const TEST_CLIENT_ID = 'testClientId';

    public function testGetSubscribedEvents(): void
    {
        $events = SPBMarksSubscriber::getSubscribedEvents();

        static::assertCount(3, $events);
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

    private function createSubscriber(
        bool $withSettings = true,
        bool $spbEnabled = true
    ): SPBMarksSubscriber {
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId(self::TEST_CLIENT_ID);
            $settings->setClientSecret('testClientSecret');
            $settings->setSpbCheckoutEnabled($spbEnabled);
        }

        $settingsService = new SettingsServiceMock($settings);

        return new SPBMarksSubscriber(
            $settingsService,
            new PaymentMethodUtilMock()
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

    private function createSalesChannelContext(): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
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

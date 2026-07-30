<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Generator;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityStateService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\FundingSubscriber;
use Swag\PayPal\Storefront\Data\Service\FundingEligibilityDataService;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class FundingSubscriberTest extends TestCase
{
    private const TEST_CLIENT_ID = 'testClientId';

    private SystemConfigService $systemConfigService;

    private Session $session;

    private RequestStack $requestStack;

    private FundingSubscriber $subscriber;

    private FundingEligibilityDataService $dataService;

    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithoutCredentials();

        $credentialsUtil = new CredentialsUtil($this->systemConfigService);

        $localeCodeProvider = $this->createMock(LocaleCodeProvider::class);
        $localeCodeProvider->method('getFormattedLocaleCode')->willReturn('en_GB');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->atMost(2))->method('generate')->willReturn('/paypal/payment-method-eligibility');

        $this->session = new Session(new MockArraySessionStorage());
        $this->session->set(MethodEligibilityRoute::SESSION_KEY, [SEPAHandler::class]);

        $request = new Request();
        $request->setSession($this->session);

        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->method('isPaymentMethodActive')
            ->with(static::isInstanceOf(SalesChannelContext::class), \array_values(MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS))
            ->willReturn(true);

        $this->subscriber = new FundingSubscriber(
            new SettingsValidationService($this->systemConfigService, new NullLogger()),
            $this->dataService = new FundingEligibilityDataService(
                $credentialsUtil,
                $this->systemConfigService,
                $localeCodeProvider,
                $router,
                $this->requestStack,
                new MethodEligibilityStateService($this->createMock(SalesChannelContextPersister::class)),
            ),
            $paymentMethodUtil,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = FundingSubscriber::getSubscribedEvents();

        static::assertCount(4, $events);
        // @deprecated tag:v11.0.0 - Remove this line below.
        static::assertSame('addFundingAvailabilityDataToFooter', $events[FooterPageletLoadedEvent::class]);
        static::assertSame('addFundingAvailabilityDataToPage', $events[GenericPageLoadedEvent::class]);
        static::assertSame(['removeFundingAvailabilityDataFromPage', -1], $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame(['removeFundingAvailabilityDataFromPage', -1], $events[CheckoutRegisterPageLoadedEvent::class]);
    }

    public function testAddNoSettings(): void
    {
        $event = $this->createFooterPageletLoadedEvent();
        // @deprecated tag:v11.0.0 - Remove this line below.
        $this->subscriber->addFundingAvailabilityDataToFooter($event);

        static::assertFalse($event->getPagelet()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed.
     */
    public function testAddFundingAvailabilityDataToFooter(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');
        $event = $this->createFooterPageletLoadedEvent();

        $this->subscriber->addFundingAvailabilityDataToFooter($event);

        $extension = $event->getPagelet()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION);

        static::assertInstanceOf(FundingEligibilityData::class, $extension);
        static::assertSame(self::TEST_CLIENT_ID, $extension->getClientId());
        static::assertSame('EUR', $extension->getCurrency());
        static::assertSame('en_GB', $extension->getLanguageIso());
        static::assertSame(\mb_strtolower(ConstantsV2::INTENT_CAPTURE), $extension->getIntent());
        static::assertSame('/paypal/payment-method-eligibility', $extension->getMethodEligibilityUrl());
        static::assertSame(['SEPA'], $extension->getFilteredPaymentMethods());
    }

    public function testAddFundingAvailabilityDataToPageNoSettings(): void
    {
        $event = $this->createGenericPageLoadedEvent();

        $this->subscriber->addFundingAvailabilityDataToPage($event);

        static::assertFalse($event->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    public function testAddFundingAvailabilityDataToPageNoActivePaymentMethods(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->method('isPaymentMethodActive')
            ->willReturn(false);

        $this->subscriber = new FundingSubscriber(
            new SettingsValidationService($this->systemConfigService, new NullLogger()),
            $this->dataService,
            $paymentMethodUtil,
        );

        $event = $this->createGenericPageLoadedEvent();
        $this->subscriber->addFundingAvailabilityDataToPage($event);

        static::assertFalse($event->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    public function testAddFundingAvailabilityDataToPage(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');
        $event = $this->createGenericPageLoadedEvent();

        $this->subscriber->addFundingAvailabilityDataToPage($event);

        $extension = $event->getPage()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION);

        static::assertInstanceOf(FundingEligibilityData::class, $extension);
        static::assertSame(self::TEST_CLIENT_ID, $extension->getClientId());
        static::assertSame('EUR', $extension->getCurrency());
        static::assertSame('en_GB', $extension->getLanguageIso());
        static::assertSame(\mb_strtolower(ConstantsV2::INTENT_CAPTURE), $extension->getIntent());
        static::assertSame('/paypal/payment-method-eligibility', $extension->getMethodEligibilityUrl());
        static::assertSame(['SEPA'], $extension->getFilteredPaymentMethods());
    }

    public function testAddFundingAvailabilityDataToPageWithoutSession(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');
        $event = $this->createGenericPageLoadedEvent();

        $this->requestStack->pop();
        $this->requestStack->push(new Request());

        $this->subscriber->addFundingAvailabilityDataToPage($event);

        $extension = $event->getPage()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION);

        static::assertInstanceOf(FundingEligibilityData::class, $extension);
        static::assertSame([], $extension->getFilteredPaymentMethods());
    }

    public function testRemoveFundingAvailabilityDataFromCheckoutConfirmPage(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');

        // Add the extension via GenericPageLoadedEvent
        $genericEvent = $this->createGenericPageLoadedEvent();
        $this->subscriber->addFundingAvailabilityDataToPage($genericEvent);
        static::assertTrue($genericEvent->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));

        // Create CheckoutConfirmPage with the extension
        $salesChannelContext = Generator::generateSalesChannelContext();
        $page = new CheckoutConfirmPage();
        $page->addExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION, $genericEvent->getPage()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));

        $confirmEvent = new CheckoutConfirmPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );

        // Remove the extension
        $this->subscriber->removeFundingAvailabilityDataFromPage($confirmEvent);

        static::assertFalse($confirmEvent->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    public function testRemoveFundingAvailabilityDataFromCheckoutRegisterPage(): void
    {
        $this->systemConfigService->set(Settings::CLIENT_ID, self::TEST_CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'testClientSecret');

        // First add the extension via GenericPageLoadedEvent
        $genericEvent = $this->createGenericPageLoadedEvent();
        $this->subscriber->addFundingAvailabilityDataToPage($genericEvent);
        static::assertTrue($genericEvent->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));

        // Create CheckoutRegisterPage with the extension
        $salesChannelContext = Generator::generateSalesChannelContext();
        $page = new CheckoutRegisterPage();
        $page->addExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION, $genericEvent->getPage()->getExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));

        $registerEvent = new CheckoutRegisterPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );

        // Remove the extension
        $this->subscriber->removeFundingAvailabilityDataFromPage($registerEvent);

        static::assertFalse($registerEvent->getPage()->hasExtension(FundingSubscriber::FUNDING_ELIGIBILITY_EXTENSION));
    }

    private function createFooterPageletLoadedEvent(): FooterPageletLoadedEvent
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        return new FooterPageletLoadedEvent(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
            new Request()
        );
    }

    private function createGenericPageLoadedEvent(): GenericPageLoadedEvent
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $page = new Page();

        return new GenericPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );
    }
}

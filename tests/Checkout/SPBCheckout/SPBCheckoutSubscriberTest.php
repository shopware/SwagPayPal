<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutSubscriber;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SPBCheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const TEST_CLIENT_ID = 'testClientId';

    private PaymentMethodUtil $paymentMethodUtil;

    private string $paypalPaymentMethodId;

    private Session $session;

    protected function setUp(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->paypalPaymentMethodId = (string) $paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        $this->session = new Session(new MockArraySessionStorage());
    }

    protected function tearDown(): void
    {
        $this->removePaymentMethodFromDefaultsSalesChannel($this->paypalPaymentMethodId);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SPBCheckoutSubscriber::getSubscribedEvents();

        static::assertCount(2, $events);
        static::assertSame('onAccountOrderEditLoaded', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
    }

    public function testOnAccountOrderEditLoadedNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createEditOrderPageLoadedEvent();
        $subscriber->onAccountOrderEditLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnAccountOrderEditLoaded(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEditOrderPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);
        $this->assertSpbCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmSPBNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBPayPalNotInActiveSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent(false);
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(
            new PaymentMethodCollection([])
        );
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedSPBDisabledWithGermanMerchantLocation(): void
    {
        $subscriber = $this->createSubscriber(true, true, false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->assertSpbCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmLoadedSPBDisabledWithCartErrors(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getPage()->getCart()->addErrors(new ShippingMethodBlockedError('foo'));
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedPayerIdInRequest(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getRequest()->query->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, 'testOrderId');
        $event->getRequest()->setSession($this->session);
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
        $requestStack = $this->getContainer()->get('request_stack');
        static::assertNotNull($requestStack);
        $requestStack->push($event->getRequest());
        $request = $requestStack->getCurrentRequest();
        static::assertNotNull($request);
        $session = $request->getSession();
        static::assertInstanceOf(Session::class, $session);
        $flashBag = $session->getFlashBag();
        static::assertCount(1, $flashBag->get('success'));
    }

    public function testOnCheckoutConfirmLoadedSPBWithCustomLanguage(): void
    {
        $subscriber = $this->createSubscriber(true, true, true, 'en_GB');
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('en_GB', $spbExtension->getLanguageIso());
        static::assertSame($this->paypalPaymentMethodId, $spbExtension->getPaymentMethodId());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $spbExtension->getIntent());
        static::assertSame('gold', $spbExtension->getButtonColor());
        static::assertSame('rect', $spbExtension->getButtonShape());
        static::assertFalse($spbExtension->getUseAlternativePaymentMethods());
        static::assertNotContains('sofort', $spbExtension->getDisabledAlternativePaymentMethods());
    }

    public function testOnCheckoutConfirmLoadedSPBWithDisabledAPM(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getPage()->getCart()->setPrice($this->createCartPrice(0.1, 0.12, 0.1));
        $this->addPaymentMethodToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertContains('sofort', $spbExtension->getDisabledAlternativePaymentMethods());
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function testAddNecessaryRequestParameter(): void
    {
        $subscriber = $this->createSubscriber();

        $testButtonId = 'testButtonId';
        $testOrderId = 'testOrderId';
        $storefrontRequest = new Request([], [
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => $testButtonId,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $testOrderId,
        ], [
            '_route' => 'frontend.account.edit-order.update-order',
        ]);
        $storeApiRequest = new Request();
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addNecessaryRequestParameter($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(2, $requestParameters);
        static::assertTrue($requestParameters->has(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertTrue($requestParameters->has(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME));
        static::assertSame($testButtonId, $requestParameters->get(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertSame($testOrderId, $requestParameters->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME));
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed
     */
    public function testAddNecessaryRequestParameterWrongRoute(): void
    {
        $subscriber = $this->createSubscriber();

        $storefrontRequest = new Request([], [], ['_route' => 'wrong.route']);
        $storeApiRequest = new Request();
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addNecessaryRequestParameter($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(0, $requestParameters);
    }

    private function createSubscriber(
        bool $withSettings = true,
        bool $spbEnabled = true,
        bool $nonGermanMerchantLocation = true,
        ?string $languageIso = null
    ): SPBCheckoutSubscriber {
        $settings = $this->createSystemConfigServiceMock($withSettings ? [
            Settings::CLIENT_ID => self::TEST_CLIENT_ID,
            Settings::CLIENT_SECRET => 'testClientSecret',
            Settings::SPB_CHECKOUT_ENABLED => $spbEnabled,
            Settings::MERCHANT_LOCATION => $nonGermanMerchantLocation ? Settings::MERCHANT_LOCATION_OTHER : Settings::MERCHANT_LOCATION_GERMANY,
            Settings::SPB_BUTTON_LANGUAGE_ISO => $languageIso,
        ] : []);

        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $spbDataService = new SPBCheckoutDataService(
            $this->paymentMethodUtil,
            $localeCodeProvider,
            $router,
            $settings
        );

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        return new SPBCheckoutSubscriber(
            new SettingsValidationService($settings, new NullLogger()),
            $settings,
            $spbDataService,
            $this->paymentMethodUtil,
            $this->session,
            $translator,
            new NullLogger()
        );
    }

    private function createConfirmPageLoadedEvent(bool $withPayPalPaymentMethod = true): CheckoutConfirmPageLoadedEvent
    {
        $paymentCollection = new PaymentMethodCollection();
        if ($withPayPalPaymentMethod) {
            $paypalPaymentMethod = new PaymentMethodEntity();
            $paypalPaymentMethod->setId($this->paypalPaymentMethodId);

            $paymentCollection->add($paypalPaymentMethod);
        }

        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $this->paypalPaymentMethodId
        );

        $page = new CheckoutConfirmPage();
        $page->setPaymentMethods($paymentCollection);
        $page->setShippingMethods(new ShippingMethodCollection([]));

        $page->setCart($this->createCart($this->paypalPaymentMethodId));

        return new CheckoutConfirmPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );
    }

    private function createEditOrderPageLoadedEvent(): AccountEditOrderPageLoadedEvent
    {
        $page = new AccountEditOrderPage();
        $page->setOrder($this->createOrderEntity(ConstantsForTesting::VALID_ORDER_ID));

        $paypalPaymentMethod = new PaymentMethodEntity();
        $paypalPaymentMethod->setId($this->paypalPaymentMethodId);
        $paymentCollection = new PaymentMethodCollection([$paypalPaymentMethod]);
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $this->paypalPaymentMethodId
        );

        $page->setPaymentMethods($paymentCollection);

        return new AccountEditOrderPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function assertSpbCheckoutButtonData(PageLoadedEvent $event): void
    {
        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('de_DE', $spbExtension->getLanguageIso());
        static::assertSame($this->paypalPaymentMethodId, $spbExtension->getPaymentMethodId());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $spbExtension->getIntent());
        static::assertFalse($spbExtension->getUseAlternativePaymentMethods());
        static::assertSame('/store-api/paypal/create-order', $spbExtension->getCreateOrderUrl());
        static::assertStringContainsString('/checkout/confirm', $spbExtension->getCheckoutConfirmUrl());
        static::assertSame('/store-api/paypal/error', $spbExtension->getAddErrorUrl());

        if ($event instanceof AccountEditOrderPageLoadedEvent) {
            $accountOrderEditUrl = $spbExtension->getAccountOrderEditUrl();
            static::assertNotNull($accountOrderEditUrl);
            static::assertStringContainsString('/account/order/edit', $accountOrderEditUrl);
            $orderId = $spbExtension->getOrderId();
            static::assertNotNull($orderId);
            static::assertSame(ConstantsForTesting::VALID_ORDER_ID, $orderId);
        } else {
            static::assertNull($spbExtension->getAccountOrderEditUrl());
            static::assertNull($spbExtension->getOrderId());
        }
    }
}

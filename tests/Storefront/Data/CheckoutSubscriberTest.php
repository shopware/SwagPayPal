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
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\RestApi\V1\Resource\IdentityResource;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\CheckoutDataSubscriber;
use Swag\PayPal\Storefront\Data\Service\ACDCCheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SEPACheckoutDataService;
use Swag\PayPal\Storefront\Data\Service\SPBCheckoutDataService;
use Swag\PayPal\Storefront\Data\Struct\AbstractCheckoutData;
use Swag\PayPal\Storefront\Data\Struct\ACDCCheckoutData;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ClientTokenResponseFixture;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const TEST_CLIENT_ID = 'testClientId';

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private EventDispatcherMock $eventDispatcher;

    protected function setUp(): void
    {
        $this->paymentMethodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);
        $this->eventDispatcher = new EventDispatcherMock();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CheckoutDataSubscriber::getSubscribedEvents();

        static::assertCount(2, $events);
        static::assertSame('onAccountOrderEditLoaded', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnAccountOrderEditSPBDisabled(string $paymentMethodId, string $extensionId, string $assertionMethod): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber([
            Settings::SPB_CHECKOUT_ENABLED => false,
            Settings::SPB_SHOW_PAY_LATER => false,
        ]);
        $event = $this->createEditOrderPageLoadedEvent($paymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);

        if ($extensionId === PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID) {
            static::assertFalse($event->getPage()->hasExtension($extensionId));
        } else {
            $this->$assertionMethod($event, $paymentMethodId);
        }
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnAccountOrderEditPaymentMethodNotInActiveSalesChannel(string $paymentMethodId, string $extensionId): void
    {
        $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber();
        $event = $this->createEditOrderPageLoadedEvent(null);
        $subscriber->onAccountOrderEditLoaded($event);

        static::assertFalse($event->getPage()->hasExtension($extensionId));
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnAccountOrderEditLoadedNoSettings(string $paymentMethodId, string $extensionId): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber([
            Settings::CLIENT_ID => null,
            Settings::CLIENT_SECRET => null,
        ]);
        $event = $this->createEditOrderPageLoadedEvent($paymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);

        static::assertFalse($event->getPage()->hasExtension($extensionId));
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnAccountOrderEditLoaded(string $paymentMethodId, string $extensionId, string $assertionMethod): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber();
        $event = $this->createEditOrderPageLoadedEvent($paymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);
        $this->$assertionMethod($event, $paymentMethodId);
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmSPBDisabled(string $paymentMethodId, string $extensionId, string $assertionMethod): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber([
            Settings::SPB_CHECKOUT_ENABLED => false,
            Settings::SPB_SHOW_PAY_LATER => false,
        ]);
        $event = $this->createConfirmPageLoadedEvent($paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        if ($extensionId === PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID) {
            static::assertFalse($event->getPage()->hasExtension($extensionId));
        } else {
            $this->$assertionMethod($event, $paymentMethodId);
        }
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmNoSettings(string $paymentMethodId, string $extensionId): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber([
            Settings::CLIENT_ID => null,
            Settings::CLIENT_SECRET => null,
        ]);
        $event = $this->createConfirmPageLoadedEvent($paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension($extensionId));
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmPaymentMethodNotInActiveSalesChannel(string $paymentMethodId, string $extensionId): void
    {
        $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent(null);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension($extensionId));
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmLoaded(string $paymentMethodId, string $extensionId, string $assertionMethod): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent($paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->$assertionMethod($event, $paymentMethodId);
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmLoadedDisabledWithCartErrors(string $paymentMethodId, string $extensionId): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent($paymentMethodId);
        $event->getPage()->getCart()->addErrors(new ShippingMethodBlockedError('foo'));
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension($extensionId));
    }

    /**
     * @dataProvider dataProviderPaymentMethods
     */
    public function testOnCheckoutConfirmLoadedWithCustomLanguage(string $paymentMethodId, string $extensionId): void
    {
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        $subscriber = $this->createSubscriber([
            Settings::SPB_BUTTON_LANGUAGE_ISO => 'en_GB',
        ]);
        $event = $this->createConfirmPageLoadedEvent($paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var AbstractCheckoutData|null $extension */
        $extension = $event->getPage()->getExtension($extensionId);

        static::assertNotNull($extension);
        static::assertSame(self::TEST_CLIENT_ID, $extension->getClientId());
        static::assertSame('EUR', $extension->getCurrency());
        static::assertSame('en_GB', $extension->getLanguageIso());
        static::assertSame($paymentMethodId, $extension->getPaymentMethodId());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $extension->getIntent());
        static::assertSame('rect', $extension->getButtonShape());
    }

    public function dataProviderPaymentMethods(): iterable
    {
        $paymentMethodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);

        return [
            [
                (string) $paymentMethodDataRegistry->getEntityIdFromData(
                    $paymentMethodDataRegistry->getPaymentMethod(ACDCMethodData::class),
                    Context::createDefaultContext()
                ),
                ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID,
                'assertAcdcCheckoutButtonData',
            ],
            [
                (string) $paymentMethodDataRegistry->getEntityIdFromData(
                    $paymentMethodDataRegistry->getPaymentMethod(PayPalMethodData::class),
                    Context::createDefaultContext()
                ),
                PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID,
                'assertSpbCheckoutButtonData',
            ],
        ];
    }

    private function createSubscriber(array $settingsOverride = []): CheckoutDataSubscriber
    {
        $settings = $this->createSystemConfigServiceMock(\array_merge([
            Settings::CLIENT_ID => self::TEST_CLIENT_ID,
            Settings::CLIENT_SECRET => 'testClientSecret',
            Settings::SPB_CHECKOUT_ENABLED => true,
            Settings::MERCHANT_LOCATION => Settings::MERCHANT_LOCATION_OTHER,
            Settings::SPB_SHOW_PAY_LATER => true,
        ], $settingsOverride));
        $credentialsUtil = new CredentialsUtil($settings);

        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $sepaDataService = new SEPACheckoutDataService(
            $this->paymentMethodDataRegistry,
            new IdentityResource($this->createPayPalClientFactoryWithService($settings)),
            $localeCodeProvider,
            $router,
            $settings,
            $credentialsUtil
        );

        $acdcDataService = new ACDCCheckoutDataService(
            $this->paymentMethodDataRegistry,
            new IdentityResource($this->createPayPalClientFactoryWithService($settings)),
            $localeCodeProvider,
            $router,
            $settings,
            $credentialsUtil
        );

        $spbDataService = new SPBCheckoutDataService(
            $this->paymentMethodDataRegistry,
            new IdentityResource($this->createPayPalClientFactoryWithService($settings)),
            $localeCodeProvider,
            $router,
            $settings,
            $credentialsUtil
        );

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getFlashbag')->willReturn(new FlashBag());

        $sepaMethodDataMock = $this->createMock(SEPAMethodData::class);
        $sepaMethodDataMock->method('getCheckoutDataService')->willReturn($sepaDataService);
        $sepaMethodDataMock->method('getCheckoutTemplateExtensionId')->willReturn(SEPAMethodData::PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID);
        $sepaMethodDataMock->method('getHandler')->willReturn(SEPAHandler::class);

        $acdcMethodDataMock = $this->createMock(ACDCMethodData::class);
        $acdcMethodDataMock->method('getCheckoutDataService')->willReturn($acdcDataService);
        $acdcMethodDataMock->method('getCheckoutTemplateExtensionId')->willReturn(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID);
        $acdcMethodDataMock->method('getHandler')->willReturn(ACDCHandler::class);

        $spbMethodDataMock = $this->createMock(PayPalMethodData::class);
        $spbMethodDataMock->method('getCheckoutDataService')->willReturn($spbDataService);
        $spbMethodDataMock->method('getCheckoutTemplateExtensionId')->willReturn(PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);
        $spbMethodDataMock->method('getHandler')->willReturn(PayPalPaymentHandler::class);

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        return new CheckoutDataSubscriber(
            new NullLogger(),
            new SettingsValidationService($settings, new NullLogger()),
            $sessionMock,
            $translator,
            $this->eventDispatcher,
            [
                $acdcMethodDataMock,
                $sepaMethodDataMock,
                $spbMethodDataMock,
            ]
        );
    }

    private function createConfirmPageLoadedEvent(?string $paymentMethodId): CheckoutConfirmPageLoadedEvent
    {
        $paymentCollection = new PaymentMethodCollection();
        if ($paymentMethodId) {
            $paypalPaymentMethod = new PaymentMethodEntity();
            $paypalPaymentMethod->setId($paymentMethodId);

            $paymentCollection->add($paypalPaymentMethod);
        }

        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), $paymentCollection, $paymentMethodId, true, $paymentMethodId === null);

        $page = new CheckoutConfirmPage();
        $page->setPaymentMethods($paymentCollection);
        $page->setShippingMethods(new ShippingMethodCollection([]));

        $page->setCart($this->createCart($paymentMethodId ?? Uuid::randomHex()));

        return new CheckoutConfirmPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );
    }

    private function createEditOrderPageLoadedEvent(?string $paymentMethodId): AccountEditOrderPageLoadedEvent
    {
        $page = new AccountEditOrderPage();
        $page->setOrder($this->createOrderEntity(ConstantsForTesting::VALID_ORDER_ID));

        $paypalPaymentMethod = new PaymentMethodEntity();
        $paypalPaymentMethod->setId($paymentMethodId ?? Uuid::randomHex());
        $paymentCollection = new PaymentMethodCollection([$paypalPaymentMethod]);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), $paymentCollection, $paymentMethodId, true, $paymentMethodId === null);

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
    private function assertAcdcCheckoutButtonData(PageLoadedEvent $event, string $paymentMethodId): void
    {
        /** @var ACDCCheckoutData|null $acdcExtension */
        $acdcExtension = $event->getPage()->getExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID);

        static::assertNotNull($acdcExtension);
        static::assertSame(self::TEST_CLIENT_ID, $acdcExtension->getClientId());
        static::assertSame(ClientTokenResponseFixture::CLIENT_TOKEN, $acdcExtension->getClientToken());
        static::assertSame('EUR', $acdcExtension->getCurrency());
        static::assertSame('de_DE', $acdcExtension->getLanguageIso());
        static::assertSame($paymentMethodId, $acdcExtension->getPaymentMethodId());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $acdcExtension->getIntent());
        static::assertSame('/store-api/paypal/create-order', $acdcExtension->getCreateOrderUrl());
        static::assertStringContainsString('/checkout/confirm', $acdcExtension->getCheckoutConfirmUrl());
        static::assertSame('/store-api/paypal/error', $acdcExtension->getAddErrorUrl());

        if ($event instanceof AccountEditOrderPageLoadedEvent) {
            $accountOrderEditUrl = $acdcExtension->getAccountOrderEditUrl();
            static::assertNotNull($accountOrderEditUrl);
            static::assertStringContainsString('/account/order/edit', $accountOrderEditUrl);
            $orderId = $acdcExtension->getOrderId();
            static::assertNotNull($orderId);
            static::assertSame(ConstantsForTesting::VALID_ORDER_ID, $orderId);
        } else {
            static::assertNull($acdcExtension->getAccountOrderEditUrl());
            static::assertNull($acdcExtension->getOrderId());
        }
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function assertSpbCheckoutButtonData(PageLoadedEvent $event, string $paymentMethodId): void
    {
        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('de_DE', $spbExtension->getLanguageIso());
        static::assertSame($paymentMethodId, $spbExtension->getPaymentMethodId());
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

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\APM;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\ACDC\ACDCCheckoutFieldData;
use Swag\PayPal\Checkout\ACDC\Service\ACDCCheckoutDataService;
use Swag\PayPal\Checkout\APM\APMCheckoutSubscriber;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\SEPA\Service\SEPACheckoutDataService;
use Swag\PayPal\RestApi\V1\Resource\IdentityResource;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ClientTokenResponseFixture;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class APMCheckoutSubscriberTest extends TestCase
{
    use CartTrait;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const TEST_CLIENT_ID = 'testClientId';

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private string $paymentMethodId;

    protected function setUp(): void
    {
        /** @var PaymentMethodDataRegistry $paymentMethodDataRegistry */
        $paymentMethodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->paymentMethodId = (string) $paymentMethodDataRegistry->getEntityIdFromData(
            $paymentMethodDataRegistry->getPaymentMethod(ACDCMethodData::class),
            Context::createDefaultContext()
        );
    }

    protected function tearDown(): void
    {
        $this->removePaymentMethodFromDefaultsSalesChannel($this->paymentMethodId);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = APMCheckoutSubscriber::getSubscribedEvents();

        static::assertCount(2, $events);
        static::assertSame('onAccountOrderEditLoaded', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
    }

    public function testOnAccountOrderEditLoadedNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createEditOrderPageLoadedEvent();
        $subscriber->onAccountOrderEditLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID));
    }

    public function testOnAccountOrderEditLoaded(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEditOrderPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);
        $this->assertAcdcCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmPaymentMethodNotInActiveSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent(false);
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(
            new PaymentMethodCollection([])
        );
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoaded(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->assertAcdcCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmLoadedDisabledWithCartErrors(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getPage()->getCart()->addErrors(new ShippingMethodBlockedError('foo'));
        $this->addPaymentMethodToDefaultsSalesChannel($this->paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertFalse($event->getPage()->hasExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedWithCustomLanguage(): void
    {
        $subscriber = $this->createSubscriber(true, 'en_GB');
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPaymentMethodToDefaultsSalesChannel($this->paymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var ACDCCheckoutFieldData|null $acdcExtension */
        $acdcExtension = $event->getPage()->getExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID);

        static::assertNotNull($acdcExtension);
        static::assertSame(self::TEST_CLIENT_ID, $acdcExtension->getClientId());
        static::assertSame('EUR', $acdcExtension->getCurrency());
        static::assertSame('en_GB', $acdcExtension->getLanguageIso());
        static::assertSame($this->paymentMethodId, $acdcExtension->getPaymentMethodId());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $acdcExtension->getIntent());
        static::assertSame('rect', $acdcExtension->getButtonShape());
    }

    private function createSubscriber(
        bool $withSettings = true,
        ?string $languageIso = null
    ): APMCheckoutSubscriber {
        $settings = $this->createSystemConfigServiceMock($withSettings ? [
            Settings::CLIENT_ID => self::TEST_CLIENT_ID,
            Settings::CLIENT_SECRET => 'testClientSecret',
            Settings::SPB_BUTTON_LANGUAGE_ISO => $languageIso,
        ] : []);

        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $sepaDataService = new SEPACheckoutDataService(
            $this->paymentMethodDataRegistry,
            new IdentityResource($this->createPayPalClientFactoryWithService($settings)),
            $localeCodeProvider,
            $router,
            $settings
        );

        $acdcDataService = new ACDCCheckoutDataService(
            $this->paymentMethodDataRegistry,
            new IdentityResource($this->createPayPalClientFactoryWithService($settings)),
            $localeCodeProvider,
            $router,
            $settings
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

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        return new APMCheckoutSubscriber(
            new NullLogger(),
            new SettingsValidationService($settings, new NullLogger()),
            $sessionMock,
            $translator,
            [
                $acdcMethodDataMock,
                $sepaMethodDataMock,
            ]
        );
    }

    private function createConfirmPageLoadedEvent(bool $withPayPalPaymentMethod = true): CheckoutConfirmPageLoadedEvent
    {
        $paymentCollection = new PaymentMethodCollection();
        if ($withPayPalPaymentMethod) {
            $paypalPaymentMethod = new PaymentMethodEntity();
            $paypalPaymentMethod->setId($this->paymentMethodId);

            $paymentCollection->add($paypalPaymentMethod);
        }

        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $withPayPalPaymentMethod ? $this->paymentMethodId : null
        );

        $page = new CheckoutConfirmPage();
        $page->setPaymentMethods($paymentCollection);
        $page->setShippingMethods(new ShippingMethodCollection([]));

        $page->setCart($this->createCart($this->paymentMethodId));

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
        $paypalPaymentMethod->setId($this->paymentMethodId);
        $paymentCollection = new PaymentMethodCollection([$paypalPaymentMethod]);
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $this->paymentMethodId
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
    private function assertAcdcCheckoutButtonData(PageLoadedEvent $event): void
    {
        /** @var ACDCCheckoutFieldData|null $acdcExtension */
        $acdcExtension = $event->getPage()->getExtension(ACDCMethodData::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID);

        static::assertNotNull($acdcExtension);
        static::assertSame(self::TEST_CLIENT_ID, $acdcExtension->getClientId());
        static::assertSame(ClientTokenResponseFixture::CLIENT_TOKEN, $acdcExtension->getClientToken());
        static::assertSame('EUR', $acdcExtension->getCurrency());
        static::assertSame('de_DE', $acdcExtension->getLanguageIso());
        static::assertSame($this->paymentMethodId, $acdcExtension->getPaymentMethodId());
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
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutSubscriber;
use Swag\PayPal\Payment\Handler\AbstractPaymentHandler;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SPBCheckoutSubscriberTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;

    private const TEST_CLIENT_ID = 'testClientId';

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var string
     */
    private $paypalPaymentMethodId;

    protected function setUp(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->paypalPaymentMethodId = (string) $paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SPBCheckoutSubscriber::getSubscribedEvents();

        static::assertCount(3, $events);
        static::assertSame('onAccountOrderEditLoaded', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame('addNecessaryRequestParameter', $events[HandlePaymentMethodRouteRequestEvent::class]);
    }

    public function testOnAccountOrderEditLoadedNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createEditOrderPageLoadedEvent();
        $subscriber->onAccountOrderEditLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnAccountOrderEditLoaded(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEditOrderPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onAccountOrderEditLoaded($event);
        $this->assertSpbCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmSPBNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBPayPalNotInActiveSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(
            new PaymentMethodCollection([])
        );
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedSPBDisabledWithGermanMerchantLocation(): void
    {
        $subscriber = $this->createSubscriber(true, true, false);
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension('spbCheckoutButtonData');

        static::assertNull($spbExtension);
    }

    public function testOnCheckoutConfirmLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->assertSpbCheckoutButtonData($event);
    }

    public function testOnCheckoutConfirmLoadedPayerIdInRequest(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmPageLoadedEvent();
        $event->getRequest()->query->set(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME, 'testPaymentId');
        $event->getRequest()->query->set(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME, 'testPayerId');
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
        /** @var Session $session */
        $session = $this->getContainer()->get('session');
        $flashBag = $session->getFlashBag();
        static::assertCount(1, $flashBag->get('success'));
    }

    public function testOnCheckoutConfirmLoadedSPBWithCustomLanguage(): void
    {
        $subscriber = $this->createSubscriber(true, true, true, 'en_GB');
        $event = $this->createConfirmPageLoadedEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('en_GB', $spbExtension->getLanguageIso());
        static::assertSame($this->paypalPaymentMethodId, $spbExtension->getPaymentMethodId());
        static::assertSame(PaymentIntent::SALE, $spbExtension->getIntent());
        static::assertSame('gold', $spbExtension->getButtonColor());
        static::assertSame('rect', $spbExtension->getButtonShape());
        static::assertTrue($spbExtension->getUseAlternativePaymentMethods());
    }

    public function testAddNecessaryRequestParameter(): void
    {
        $subscriber = $this->createSubscriber();

        $testButtonId = 'testButtonId';
        $testPaymentId = 'testPaymentId';
        $testPayerId = 'testPayerId';
        $storefrontRequest = new Request([], [
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => $testButtonId,
            AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME => $testPaymentId,
            EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME => $testPayerId,
        ], [
            '_route' => 'frontend.account.edit-order.update-order',
        ]);
        $storeApiRequest = new Request();
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $event = new HandlePaymentMethodRouteRequestEvent($storefrontRequest, $storeApiRequest, $salesChannelContext);
        $subscriber->addNecessaryRequestParameter($event);

        $requestParameters = $storeApiRequest->request;
        static::assertCount(3, $requestParameters);
        static::assertTrue($requestParameters->has(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertTrue($requestParameters->has(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME));
        static::assertTrue($requestParameters->has(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME));
        static::assertSame($testButtonId, $requestParameters->get(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID));
        static::assertSame($testPaymentId, $requestParameters->get(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME));
        static::assertSame($testPayerId, $requestParameters->get(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME));
    }

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
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId(self::TEST_CLIENT_ID);
            $settings->setClientSecret('testClientSecret');
            $settings->setSpbCheckoutEnabled($spbEnabled);
            $settings->setMerchantLocation(
                $nonGermanMerchantLocation ? SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER : SwagPayPalSettingStruct::MERCHANT_LOCATION_GERMANY
            );

            if ($languageIso !== null) {
                $settings->setSpbButtonLanguageIso($languageIso);
            }
        }

        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $spbDataService = new SPBCheckoutDataService(
            $this->paymentMethodUtil,
            $localeCodeProvider,
            $router
        );

        /** @var Session $session */
        $session = $this->getContainer()->get('session');
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        return new SPBCheckoutSubscriber(
            new SettingsServiceMock($settings),
            $spbDataService,
            $this->paymentMethodUtil,
            $session,
            $translator
        );
    }

    private function createConfirmPageLoadedEvent(): CheckoutConfirmPageLoadedEvent
    {
        $paypalPaymentMethod = new PaymentMethodEntity();
        $paypalPaymentMethod->setId($this->paypalPaymentMethodId);
        $paymentCollection = new PaymentMethodCollection([$paypalPaymentMethod]);
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $this->paypalPaymentMethodId
        );

        $page = new CheckoutConfirmPage(
            $paymentCollection,
            new ShippingMethodCollection([])
        );

        $cart = new Cart('test', 'token');
        $transaction = new Transaction(
            new CalculatedPrice(
                10.9,
                10.9,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ),
            $this->paypalPaymentMethodId
        );
        $cart->setTransactions(new TransactionCollection([$transaction]));
        $page->setCart($cart);

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
        static::assertSame(PaymentIntent::SALE, $spbExtension->getIntent());
        static::assertTrue($spbExtension->getUseAlternativePaymentMethods());
        static::assertSame('/sales-channel-api/v2/_action/paypal/spb/create-payment', $spbExtension->getCreatePaymentUrl());
        static::assertStringContainsString('/checkout/confirm', $spbExtension->getCheckoutConfirmUrl());
        static::assertStringContainsString('/paypal/add-error', $spbExtension->getAddErrorUrl());
        /**
         * @deprecated tag:v2.0.0 - Will be removed without replacement
         */
        static::assertSame(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_ERROR_PARAMETER, $spbExtension->getErrorParameter());
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

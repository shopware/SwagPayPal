<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Plus;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\Checkout\Plus\PlusSubscriber;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Payment\Builder\OrderPaymentBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlusSubscriberTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use PaymentMethodTrait;
    use PaymentTransactionTrait;
    use BasicTestDataBehaviour;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const NEW_PAYMENT_NAME = 'PayPal, Lastschrift oder Kreditkarte';
    private const PAYMENT_DESCRIPTION_EXTENSION = 'Bezahlung per PayPal - einfach, schnell und sicher. Zahlung per Lastschrift oder Kreditkarte ist auch ohne ein PayPal-Konto möglich.';

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
        $this->paypalPaymentMethodId = (string) $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
    }

    public function testGetSubscribedEvents(): void
    {
        $events = PlusSubscriber::getSubscribedEvents();

        static::assertCount(3, $events);
        static::assertSame('onAccountEditOrderLoaded', $events[AccountEditOrderPageLoadedEvent::class]);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame('onCheckoutFinishLoaded', $events[CheckoutFinishPageLoadedEvent::class]);
    }

    public function testOnAccountEditOrderLoadedPlusEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEditOrderEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onAccountEditOrderLoaded($event);
        $plusExtension = $this->assertPlusExtension($event);

        static::assertSame(
            \sprintf('/store-api/v%s/order/payment', PlatformRequest::API_VERSION),
            $plusExtension->getSetPaymentRouteUrl()
        );
        static::assertSame(ConstantsForTesting::VALID_ORDER_ID, $plusExtension->getOrderId());
    }

    public function testOnAccountEditOrderLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createAccountEditOrderEvent();
        $subscriber->onAccountEditOrderLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnAccountEditOrderLoadedNoCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEditOrderEvent(false);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onAccountEditOrderLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnAccountEditOrderLoadedNoOrderTransactions(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEditOrderEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $event->getPage()->getOrder()->assign(['transactions' => null]);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage(
            \sprintf('The order with id %s is invalid or could not be found.', ConstantsForTesting::VALID_ORDER_ID)
        );
        $subscriber->onAccountEditOrderLoaded($event);
    }

    public function testOnAccountEditOrderLoadedNoFirstOrderTransaction(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEditOrderEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $event->getPage()->getOrder()->setTransactions(new OrderTransactionCollection());

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage(
            \sprintf('The order with id %s is invalid or could not be found.', ConstantsForTesting::VALID_ORDER_ID)
        );
        $subscriber->onAccountEditOrderLoaded($event);
    }

    public function testOnAccountEditOrderLoadedCreatePaymentThrowsException(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createAccountEditOrderEvent(true, ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION_WITH_PREFIX);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onAccountEditOrderLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedIsExpressCheckout(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $event->getRequest()->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);

        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedPlusNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedNoCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent(false);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedPlusEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->assertPlusExtension($event);
    }

    public function testOnCheckoutConfirmLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $this->assertPlusExtension($event);

        $selectedPaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertSame(self::NEW_PAYMENT_NAME, $selectedPaymentMethod->getTranslated()['name']);
        static::assertStringContainsString(
            self::PAYMENT_DESCRIPTION_EXTENSION,
            $selectedPaymentMethod->getTranslated()['description']
        );

        $paymentMethod = $event->getPage()->getPaymentMethods()->get($this->paypalPaymentMethodId);
        static::assertNotNull($paymentMethod);
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(
            self::PAYMENT_DESCRIPTION_EXTENSION,
            $paymentMethod->getTranslated()['description']
        );
    }

    public function testOnCheckoutConfirmLoadedPayPalPaymentMethodNotSelected(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent(true, true);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID);

        static::assertNull($plusExtension);

        $paymentMethod = $event->getPage()->getPaymentMethods()->get($this->paypalPaymentMethodId);
        static::assertNotNull($paymentMethod);
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(
            self::PAYMENT_DESCRIPTION_EXTENSION,
            $paymentMethod->getTranslated()['description']
        );
    }

    public function testOnCheckoutFinishLoadedIsNotPayPalPlus(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $event->getRequest()->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, false);

        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $event = $this->createFinishEvent();
        $translated = $this->assertFinishPage($event)->getTranslated();
        static::assertSame(self::NEW_PAYMENT_NAME, $translated['name']);
        static::assertSame(self::PAYMENT_DESCRIPTION_EXTENSION, $translated['description']);
    }

    public function testOnCheckoutFinishLoadedNotPayPalSelected(): void
    {
        $event = $this->createFinishEvent(true, true, false, true);
        $paymentMethod = $this->assertFinishPage($event);
        $translated = $paymentMethod->getTranslated();
        static::assertArrayNotHasKey('name', $translated);
        static::assertArrayNotHasKey('description', $translated);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutTransactions(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(false);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutTransaction(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(true, false);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutPaymentMethod(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(true, true, false);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutConfirmLoadedWithoutPayPalInSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent(false, false, false);
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutConfirmLoadedWithoutSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutConfirmLoadedWithSettingsButPlusDisabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutConfirmLoadedWithoutCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $event->getSalesChannelContext()->assign([
            'customer' => null,
        ]);
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    private function createConfirmEvent(
        bool $withCustomer = true,
        bool $withOtherDefaultPayment = false,
        bool $withPayPalPaymentMethod = true
    ): CheckoutConfirmPageLoadedEvent {
        $paymentCollection = new PaymentMethodCollection();
        if ($withPayPalPaymentMethod) {
            $paymentCollection->add($this->createPayPalPaymentMethod());
        }

        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            $paymentCollection,
            $this->paypalPaymentMethodId,
            $withCustomer,
            $withOtherDefaultPayment
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

        $request = $this->createRequest($salesChannelContext->getContext());

        return new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, $request);
    }

    private function createSubscriber(
        bool $withSettings = true,
        bool $plusEnabled = true
    ): PlusSubscriber {
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('testClientId');
            $settings->setClientSecret('testClientSecret');
            $settings->setPlusCheckoutEnabled($plusEnabled);
        }

        $settingsService = new SettingsServiceMock($settings);
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');
        /** @var EntityRepositoryInterface $currencyRepo */
        $currencyRepo = $this->getContainer()->get('currency.repository');

        $plusDataService = new PlusDataService(
            new CartPaymentBuilder(
                $settingsService,
                $localeCodeProvider
            ),
            new OrderPaymentBuilder(
                $settingsService,
                $localeCodeProvider,
                $currencyRepo
            ),
            $this->createPaymentResource($settings),
            $router,
            $this->paymentMethodUtil,
            $localeCodeProvider
        );

        return new PlusSubscriber($settingsService, $plusDataService, $this->paymentMethodUtil, $translator);
    }

    private function createFinishEvent(
        bool $withTransactions = true,
        bool $withTransaction = true,
        bool $withPaymentMethod = true,
        bool $withDefaultPaymentMethod = false
    ): CheckoutFinishPageLoadedEvent {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL,
            [
                SalesChannelContextService::PAYMENT_METHOD_ID => $this->paypalPaymentMethodId,
            ]
        );

        $order = $this->createOrderEntity('test-id');
        if ($withTransactions) {
            if ($withTransaction) {
                $orderTransaction = $this->createOrderTransaction();
                $orderTransaction->setPaymentMethodId('test-payment-method-id');

                if ($withPaymentMethod) {
                    $payPalPaymentMethod = $this->createPayPalPaymentMethod();
                    $orderTransaction->setPaymentMethod($payPalPaymentMethod);
                    $orderTransaction->setPaymentMethodId($payPalPaymentMethod->getId());
                } elseif ($withDefaultPaymentMethod) {
                    $paymentMethod = new PaymentMethodEntity();
                    $paymentMethod->setId('test-id');
                    $orderTransaction->setPaymentMethod($paymentMethod);
                    $orderTransaction->setPaymentMethodId($paymentMethod->getId());
                }

                $orderTransactionCollection = new OrderTransactionCollection([$orderTransaction]);
            } else {
                $orderTransactionCollection = new OrderTransactionCollection();
            }

            $order->setTransactions($orderTransactionCollection);
        }

        $page = new CheckoutFinishPage();
        $page->setOrder($order);

        $request = $this->createRequest($salesChannelContext->getContext());

        return new CheckoutFinishPageLoadedEvent($page, $salesChannelContext, $request);
    }

    private function createPayPalPaymentMethod(): PaymentMethodEntity
    {
        $payPalPaymentMethod = new PaymentMethodEntity();
        $payPalPaymentMethod->setId($this->paypalPaymentMethodId);
        $payPalPaymentMethod->setDescription('Bezahlung per PayPal - einfach, schnell und sicher.');

        return $payPalPaymentMethod;
    }

    private function createRequest(Context $context): Request
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'de-DE'));

        /** @var EntityRepositoryInterface $snippetSetRepository */
        $snippetSetRepository = $this->getContainer()->get('snippet_set.repository');
        $snippetSetId = $snippetSetRepository->search($criteria, $context)->first()->getId();

        $request = new Request();
        $request->attributes->add([SalesChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID => $snippetSetId]);
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, true);

        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push($request);

        return $request;
    }

    private function createAccountEditOrderEvent(bool $withCustomer = true, ?string $orderNumber = null): AccountEditOrderPageLoadedEvent
    {
        $order = $this->createOrderEntity(ConstantsForTesting::VALID_ORDER_ID, $orderNumber);
        $orderTransaction = $this->createOrderTransaction();
        $orderTransaction->setOrderId($order->getId());
        $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        $payPalPaymentMethod = $this->createPayPalPaymentMethod();
        $paymentCollection = new PaymentMethodCollection([$payPalPaymentMethod]);

        $accountOrderEditPage = new AccountEditOrderPage();
        $accountOrderEditPage->setOrder($order);
        $accountOrderEditPage->setPaymentMethods($paymentCollection);

        return new AccountEditOrderPageLoadedEvent(
            $accountOrderEditPage,
            $this->createSalesChannelContext(
                $this->getContainer(),
                $paymentCollection,
                $this->paypalPaymentMethodId,
                $withCustomer
            ),
            new Request()
        );
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function assertPlusExtension(PageLoadedEvent $event): PlusData
    {
        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension(PlusSubscriber::PAYPAL_PLUS_DATA_EXTENSION_ID);

        static::assertNotNull($plusExtension);
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $plusExtension->getApprovalUrl());
        static::assertSame(2, \mb_strlen($plusExtension->getCustomerCountryIso()));
        static::assertSame('live', $plusExtension->getMode());
        static::assertSame('de_DE', $plusExtension->getCustomerSelectedLanguage());
        static::assertSame($this->paypalPaymentMethodId, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getPaypalPaymentId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_TOKEN, $plusExtension->getPaypalToken());
        static::assertSame(
            \sprintf('/sales-channel-api/v%s/checkout/order', PlatformRequest::API_VERSION),
            $plusExtension->getCheckoutOrderUrl()
        );
        static::assertSame(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, $plusExtension->getIsEnabledParameterName());
        static::assertSame($event->getContext()->getLanguageId(), $plusExtension->getLanguageId());

        return $plusExtension;
    }

    private function assertFinishPage(CheckoutFinishPageLoadedEvent $event): PaymentMethodEntity
    {
        $subscriber = $this->createSubscriber();
        $this->addPayPalToDefaultsSalesChannel($this->paypalPaymentMethodId);
        $subscriber->onCheckoutFinishLoaded($event);

        $transactions = $event->getPage()->getOrder()->getTransactions();
        static::assertNotNull($transactions);
        $transaction = $transactions->first();
        static::assertNotNull($transaction);
        $paymentMethod = $transaction->getPaymentMethod();
        static::assertNotNull($paymentMethod);

        return $paymentMethod;
    }
}

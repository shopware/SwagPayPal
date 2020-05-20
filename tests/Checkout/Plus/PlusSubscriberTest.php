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
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\Checkout\Plus\PlusSubscriber;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
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
    use IntegrationTestBehaviour;
    use PaymentTransactionTrait;
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

        static::assertCount(2, $events);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame('onCheckoutFinishLoaded', $events[CheckoutFinishPageLoadedEvent::class]);
    }

    public function testOnCheckoutConfirmLoadedIsExpressCheckout(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $event->getRequest()->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);

        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedPlusNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedNoCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent(false);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedPlusEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension('payPalPlusData');

        static::assertNotNull($plusExtension);
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $plusExtension->getApprovalUrl());
        static::assertSame(2, \mb_strlen($plusExtension->getCustomerCountryIso()));
        static::assertSame('live', $plusExtension->getMode());
        static::assertSame('de_DE', $plusExtension->getCustomerSelectedLanguage());
        static::assertSame($this->paypalPaymentMethodId, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getPaypalPaymentId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_TOKEN, $plusExtension->getPaypalToken());
        static::assertSame('/sales-channel-api/v2/checkout/order', $plusExtension->getCheckoutOrderUrl());
        static::assertSame(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, $plusExtension->getIsEnabledParameterName());
    }

    public function testOnCheckoutConfirmLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension('payPalPlusData');

        static::assertNotNull($plusExtension);
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $plusExtension->getApprovalUrl());
        static::assertSame(2, \mb_strlen($plusExtension->getCustomerCountryIso()));
        static::assertSame('live', $plusExtension->getMode());
        static::assertSame('de_DE', $plusExtension->getCustomerSelectedLanguage());
        static::assertSame($this->paypalPaymentMethodId, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getPaypalPaymentId());

        $selectedPaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertSame(self::NEW_PAYMENT_NAME, $selectedPaymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $selectedPaymentMethod->getTranslated()['description']);

        $paymentMethod = $event->getPage()->getPaymentMethods()->get($this->paypalPaymentMethodId);
        static::assertNotNull($paymentMethod);
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $paymentMethod->getTranslated()['description']);
    }

    public function testOnCheckoutConfirmLoadedPayPalPaymentMethodNotSelected(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent(true, true);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension('payPalPlusData');

        static::assertNull($plusExtension);

        $paymentMethod = $event->getPage()->getPaymentMethods()->get($this->paypalPaymentMethodId);
        static::assertNotNull($paymentMethod);
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $paymentMethod->getTranslated()['description']);
    }

    public function testOnCheckoutFinishLoadedIsNotPayPalPlus(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $event->getRequest()->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, false);

        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $transactions = $event->getPage()->getOrder()->getTransactions();
        static::assertNotNull($transactions);
        $transaction = $transactions->first();
        static::assertNotNull($transaction);
        $paymentMethod = $transaction->getPaymentMethod();
        static::assertNotNull($paymentMethod);
        $translated = $paymentMethod->getTranslated();
        static::assertSame(self::NEW_PAYMENT_NAME, $translated['name']);
        static::assertSame(self::PAYMENT_DESCRIPTION_EXTENSION, $translated['description']);
    }

    public function testOnCheckoutFinishLoadedNotPayPalSelected(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(true, true, false, true);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $transactions = $event->getPage()->getOrder()->getTransactions();
        static::assertNotNull($transactions);
        $transaction = $transactions->first();
        static::assertNotNull($transaction);
        $paymentMethod = $transaction->getPaymentMethod();
        static::assertNotNull($paymentMethod);
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
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutTransactions(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(false);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutTransaction(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(true, false);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutPaymentMethod(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent(true, true, false);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutPayPalInSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithSettingsButPlusDisabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedWithoutCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createConfirmEvent();
        $event->getSalesChannelContext()->assign([
            'customer' => null,
        ]);
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    private function createConfirmEvent(bool $withCustomer = true, bool $withOtherDefaultPayment = false): CheckoutConfirmPageLoadedEvent
    {
        /** @var EntityRepositoryInterface $languageRepo */
        $languageRepo = $this->getContainer()->get('language.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('language.locale');
        $criteria->addFilter(new EqualsFilter('language.locale.code', 'de-DE'));

        $languageId = $languageRepo->searchIds($criteria, Context::createDefaultContext())->firstId();

        $options = [
            SalesChannelContextService::LANGUAGE_ID => $languageId,
            SalesChannelContextService::PAYMENT_METHOD_ID => $this->paypalPaymentMethodId,
        ];
        if ($withCustomer) {
            $options[SalesChannelContextService::CUSTOMER_ID] = $this->createCustomer();
        }

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL,
            $options
        );

        $payPalPaymentMethod = $this->createPayPalPaymentMethod();
        $paymentCollection = new PaymentMethodCollection([$payPalPaymentMethod]);

        if ($withOtherDefaultPayment) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId('test-id');
            $salesChannelContext = new SalesChannelContext(
                $salesChannelContext->getContext(),
                $salesChannelContext->getToken(),
                $salesChannelContext->getSalesChannel(),
                $salesChannelContext->getCurrency(),
                $salesChannelContext->getCurrentCustomerGroup(),
                $salesChannelContext->getFallbackCustomerGroup(),
                $salesChannelContext->getTaxRules(),
                $paymentMethod,
                $salesChannelContext->getShippingMethod(),
                $salesChannelContext->getShippingLocation(),
                $salesChannelContext->getCustomer(),
                $salesChannelContext->getRuleIds()
            );
            $paymentCollection->add($paymentMethod);
        }

        $salesChannelContext->getSalesChannel()->setPaymentMethods(
            $paymentCollection
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
            (string) $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext())
        );
        $cart->setTransactions(new TransactionCollection([$transaction]));
        $page->setCart($cart);

        $request = $this->createRequest($salesChannelContext->getContext());

        return new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, $request);
    }

    private function createCustomer(): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        $customerRepo->upsert([$customer], Context::createDefaultContext());

        return $customerId;
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
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $plusDataService = new PlusDataService(
            new CartPaymentBuilder(
                $settingsService,
                $salesChannelRepo,
                $localeCodeProvider
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
            'token',
            Defaults::SALES_CHANNEL,
            [
                SalesChannelContextService::PAYMENT_METHOD_ID => $this->paypalPaymentMethodId,
            ]
        );

        $order = $this->createOrderEntity('test-id');
        if ($withTransactions) {
            if ($withTransaction) {
                $orderTransaction = $this->createOrderTransaction(null);

                if ($withPaymentMethod) {
                    $payPalPaymentMethod = $this->createPayPalPaymentMethod();
                    $orderTransaction->setPaymentMethod($payPalPaymentMethod);
                } elseif ($withDefaultPaymentMethod) {
                    $paymentMethod = new PaymentMethodEntity();
                    $paymentMethod->setId('test-id');
                    $orderTransaction->setPaymentMethod($paymentMethod);
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

    private function addPayPalToDefaultsSalesChannel(): void
    {
        $payPalPaymentMethod = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepo->update([
            [
                'id' => Defaults::SALES_CHANNEL,
                'paymentMethods' => [
                    $payPalPaymentMethod => [
                        'id' => $payPalPaymentMethod,
                    ],
                ],
            ],
        ], Context::createDefaultContext());
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
}

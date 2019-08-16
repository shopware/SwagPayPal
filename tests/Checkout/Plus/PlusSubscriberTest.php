<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\Plus;

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
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\Checkout\Plus\PlusSubscriber;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class PlusSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    private const NEW_PAYMENT_NAME = 'New PayPal Payment Name';
    private const PAYMENT_DESCRIPTION_EXTENSION = 'Additional text for testing purpose';

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $paypalPaymentMethodId;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $paymentMethodRepo */
        $paymentMethodRepo = $this->getContainer()->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $this->paymentMethodUtil = new PaymentMethodUtil($paymentMethodRepo, $salesChannelRepo);
        $this->context = Context::createDefaultContext();
        $this->paypalPaymentMethodId = (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($this->context);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = PlusSubscriber::getSubscribedEvents();

        static::assertCount(2, $events);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame('onCheckoutFinishLoaded', $events[CheckoutFinishPageLoadedEvent::class]);
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
        if ($plusExtension === null) {
            return;
        }

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $plusExtension->getApprovalUrl());
        static::assertSame(2, \strlen($plusExtension->getCustomerCountryIso()));
        static::assertSame('live', $plusExtension->getMode());
        static::assertSame('de_DE', $plusExtension->getCustomerSelectedLanguage());
        static::assertSame($this->paypalPaymentMethodId, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getPaypalPaymentId());
        static::assertSame('/sales-channel-api/v1/checkout/order', $plusExtension->getCheckoutOrderUrl());
    }

    public function testOnCheckoutConfirmLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $subscriber = $this->createSubscriber(true, true, true);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var PlusData|null $plusExtension */
        $plusExtension = $event->getPage()->getExtension('payPalPlusData');

        static::assertNotNull($plusExtension);
        if ($plusExtension === null) {
            return;
        }

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $plusExtension->getApprovalUrl());
        static::assertSame(2, \strlen($plusExtension->getCustomerCountryIso()));
        static::assertSame('live', $plusExtension->getMode());
        static::assertSame('de_DE', $plusExtension->getCustomerSelectedLanguage());
        static::assertSame($this->paypalPaymentMethodId, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getPaypalPaymentId());

        $selectedPaymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertSame(self::NEW_PAYMENT_NAME, $selectedPaymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $selectedPaymentMethod->getTranslated()['description']);

        $paymentMethod = $event->getPage()->getPaymentMethods()->get($this->paypalPaymentMethodId);
        static::assertNotNull($paymentMethod);
        if ($paymentMethod === null) {
            return;
        }
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $paymentMethod->getTranslated()['description']);
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

    public function testOnCheckoutFinishLoadedPlusNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertNotSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
    }

    public function testOnCheckoutFinishLoadedPlusEnabledWithPaymentOverwrite(): void
    {
        $subscriber = $this->createSubscriber(true, true, true);
        $event = $this->createFinishEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutFinishLoaded($event);

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        static::assertSame(self::NEW_PAYMENT_NAME, $paymentMethod->getTranslated()['name']);
        static::assertStringContainsString(self::PAYMENT_DESCRIPTION_EXTENSION, $paymentMethod->getTranslated()['description']);
    }

    public function testOnCheckoutFinishLoadedWithoutPayPalInSalesChannel(): void
    {
        $subscriber = $this->createSubscriber(true, true, true);
        $event = $this->createConfirmEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutFinishLoadedWithoutSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutFinishLoadedWithSettingsButPlusDisabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createConfirmEvent();
        $this->addPayPalToDefaultsSalesChannel();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
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

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    private function createConfirmEvent(bool $withCustomer = true, bool $withPayPalPaymentMethod = true): CheckoutConfirmPageLoadedEvent
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

        if ($withPayPalPaymentMethod) {
            $payPalPaymentMethod = new PaymentMethodEntity();
            $payPalPaymentMethod->setId(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID);
            $salesChannelContext->getSalesChannel()->setPaymentMethods(
                new PaymentMethodCollection([
                    $payPalPaymentMethod,
                ])
            );
        }

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId($this->paypalPaymentMethodId);
        $page = new CheckoutConfirmPage(
            new PaymentMethodCollection([$paymentMethod]),
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

        return new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
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
        bool $plusEnabled = true,
        $paymentNameOverwrite = false
    ): PlusSubscriber {
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('testClientId');
            $settings->setClientSecret('testClientSecret');
            $settings->setPlusEnabled($plusEnabled);
            if ($paymentNameOverwrite) {
                $settings->setPlusOverwritePaymentName(self::NEW_PAYMENT_NAME);
                $settings->setPlusExtendPaymentDescription(self::PAYMENT_DESCRIPTION_EXTENSION);
            }
        }

        $settingsService = new SettingsServiceMock($settings);
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

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

        return new PlusSubscriber($settingsService, $plusDataService, $this->paymentMethodUtil);
    }

    private function createFinishEvent(): CheckoutFinishPageLoadedEvent
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL,
            [
                SalesChannelContextService::PAYMENT_METHOD_ID => $this->paypalPaymentMethodId,
            ]
        );

        return new CheckoutFinishPageLoadedEvent(new CheckoutFinishPage(), $salesChannelContext, new Request());
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
}

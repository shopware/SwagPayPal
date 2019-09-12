<?php declare(strict_types=1);

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
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutSubscriber;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\PaymentMethodUtilMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SPBCheckoutSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    private const TEST_CLIENT_ID = 'testClientId';

    /**
     * @var PaymentMethodUtilMock
     */
    private $paymentMethodUtil;

    protected function setUp(): void
    {
        $this->paymentMethodUtil = new PaymentMethodUtilMock();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SPBCheckoutSubscriber::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutConfirmPageLoadedEvent::class]);
    }

    public function testOnCheckoutConfirmSPBNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBPayPalNotInActiveSalesChannel(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(
            new PaymentMethodCollection([])
        );
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmSPBNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
    }

    public function testOnCheckoutConfirmLoadedSPBEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('de_DE', $spbExtension->getLanguageIso());
        static::assertSame(PaymentMethodUtilMock::PAYMENT_METHOD_ID, $spbExtension->getPaymentMethodId());
        static::assertSame(PaymentIntent::SALE, $spbExtension->getIntent());
        static::assertTrue($spbExtension->getUseAlternativePaymentMethods());
        static::assertSame('/sales-channel-api/v1/_action/paypal/spb/create-payment', $spbExtension->getCreatePaymentUrl());
        static::assertStringContainsString('/checkout/confirm', $spbExtension->getCheckoutConfirmUrl());
    }

    public function testOnCheckoutConfirmLoadedPayerIdInRequest(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEvent();
        $event->getRequest()->query->set(EcsSpbHandler::PAYPAL_PAYMENT_ID_INPUT_NAME, 'testPaymentId');
        $event->getRequest()->query->set(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME, 'testPayerId');
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID));
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->getContainer()->get('session.flash_bag');
        static::assertCount(1, $flashBag->get('success'));
    }

    public function testOnCheckoutConfirmLoadedSPBWithCustomLanguage(): void
    {
        $subscriber = $this->createSubscriber(true, true, 'en_GB');
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        /** @var SPBCheckoutButtonData|null $spbExtension */
        $spbExtension = $event->getPage()->getExtension(SPBCheckoutSubscriber::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID);

        static::assertNotNull($spbExtension);
        static::assertSame(self::TEST_CLIENT_ID, $spbExtension->getClientId());
        static::assertSame('EUR', $spbExtension->getCurrency());
        static::assertSame('en_GB', $spbExtension->getLanguageIso());
        static::assertSame(PaymentMethodUtilMock::PAYMENT_METHOD_ID, $spbExtension->getPaymentMethodId());
        static::assertSame(PaymentIntent::SALE, $spbExtension->getIntent());
        static::assertTrue($spbExtension->getUseAlternativePaymentMethods());
    }

    private function createSubscriber(
        bool $withSettings = true,
        bool $spbEnabled = true,
        ?string $languageIso = null
    ): SPBCheckoutSubscriber {
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId(self::TEST_CLIENT_ID);
            $settings->setClientSecret('testClientSecret');
            $settings->setSpbCheckoutEnabled($spbEnabled);

            if ($languageIso !== null) {
                $settings->setSpbButtonLanguageIso($languageIso);
            }
        }

        $settingsService = new SettingsServiceMock($settings);
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');

        $spbDataService = new SPBCheckoutDataService(
            $this->paymentMethodUtil,
            $localeCodeProvider,
            $router
        );

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->getContainer()->get('session.flash_bag');
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        return new SPBCheckoutSubscriber(
            $settingsService,
            $spbDataService,
            new PaymentMethodUtilMock(),
            $flashBag,
            $translator
        );
    }

    private function createEvent(): CheckoutConfirmPageLoadedEvent
    {
        /** @var EntityRepositoryInterface $languageRepo */
        $languageRepo = $this->getContainer()->get('language.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('language.locale');
        $criteria->addFilter(new EqualsFilter('language.locale.code', 'de-DE'));

        $languageId = $languageRepo->searchIds($criteria, Context::createDefaultContext())->firstId();

        $options = [
            SalesChannelContextService::LANGUAGE_ID => $languageId,
            SalesChannelContextService::CUSTOMER_ID => $this->createCustomer(),
        ];

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL,
            $options
        );

        $paypalPaymentMethod = new PaymentMethodEntity();
        $paypalPaymentMethod->setId(PaymentMethodUtilMock::PAYMENT_METHOD_ID);
        $salesChannelContext->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection([
            $paypalPaymentMethod,
        ]));

        $page = new CheckoutConfirmPage(
            new PaymentMethodCollection([]),
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
            $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext())
        );
        $cart->setTransactions(new TransactionCollection([$transaction]));
        $page->setCart($cart);

        return new CheckoutConfirmPageLoadedEvent(
            $page,
            $salesChannelContext,
            new Request()
        );
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
}

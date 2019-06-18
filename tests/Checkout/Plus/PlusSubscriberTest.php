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
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\PlusData;
use Swag\PayPal\Checkout\Plus\PlusSubscriber;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PaymentMethodIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class PlusSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    /**
     * @var PaymentMethodIdProviderMock
     */
    private $paymentMethodIdProvider;

    protected function setUp(): void
    {
        $this->paymentMethodIdProvider = new PaymentMethodIdProviderMock();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = PlusSubscriber::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertSame('onCheckoutConfirmLoaded', $events[CheckoutEvents::CHECKOUT_CONFIRM_PAGE_LOADED_EVENT]);
    }

    public function testOnCheckoutConfirmLoadedPlusNoSettings(): void
    {
        $subscriber = $this->createSubscriber(false);
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedPlusNotEnabled(): void
    {
        $subscriber = $this->createSubscriber(true, false);
        $event = $this->createEvent();
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedNoCustomer(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEvent(false);
        $subscriber->onCheckoutConfirmLoaded($event);

        static::assertNull($event->getPage()->getExtension('payPalPlusData'));
    }

    public function testOnCheckoutConfirmLoadedPlusEnabled(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createEvent();
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
        static::assertSame(PaymentMethodIdProviderMock::PAYMENT_METHOD_ID, $plusExtension->getPaymentMethodId());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $plusExtension->getRemotePaymentId());
    }

    private function createEvent(bool $withCustomer = true): CheckoutConfirmPageLoadedEvent
    {
        /** @var EntityRepositoryInterface $languageRepo */
        $languageRepo = $this->getContainer()->get('language.repository');
        $criteria = new Criteria();
        $criteria->addAssociationPath('language. locale');
        $criteria->addFilter(new EqualsFilter('language.locale.code', 'de-DE'));

        $languageId = $languageRepo->searchIds($criteria, Context::createDefaultContext())->firstId();

        $options = [SalesChannelContextService::LANGUAGE_ID => $languageId];
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
            $this->paymentMethodIdProvider->getPayPalPaymentMethodId(Context::createDefaultContext())
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

    private function createSubscriber(bool $withSettings = true, bool $plusEnabled = true): PlusSubscriber
    {
        $settings = null;
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('testClientId');
            $settings->setClientSecret('testClientSecret');
            $settings->setPayPalPlusEnabled($plusEnabled);
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
            $this->paymentMethodIdProvider,
            $localeCodeProvider
        );

        return new PlusSubscriber($settingsService, $plusDataService);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCheckoutDataServiceInterface;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalExpressCheckoutDataServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    private const CLIENT_ID = 'someClientId';

    private ExpressCheckoutDataServiceInterface $expressCheckoutDataService;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    private CartService $cartService;

    private EntityRepository $productRepository;

    private EntityRepository $customerRepository;

    private PaymentMethodUtil $paymentMethodUtil;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $this->paymentMethodUtil = $container->get(PaymentMethodUtil::class);
        $this->salesChannelContextFactory = $container->get(SalesChannelContextFactory::class);
        $this->cartService = $container->get(CartService::class);

        /** @var RouterInterface $router */
        $router = $container->get('router');

        $this->systemConfigService = $this->createDefaultSystemConfig();

        $this->expressCheckoutDataService = new PayPalExpressCheckoutDataService(
            $this->cartService,
            new LocaleCodeProvider(new LanguageRepoMock(), $this->createMock(LoggerInterface::class)),
            $router,
            $this->paymentMethodUtil,
            $this->systemConfigService,
            new CredentialsUtil($this->systemConfigService),
            new CartPriceService()
        );

        /** @var EntityRepository $productRepo */
        $productRepo = $container->get('product.repository');
        $this->productRepository = $productRepo;

        /** @var EntityRepository $customerRepo */
        $customerRepo = $container->get('customer.repository');
        $this->customerRepository = $customerRepo;
    }

    public function testGetExpressCheckoutButtonDataWithoutCart(): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext);

        static::assertNull($expressCheckoutButtonData);
    }

    public function testGetExpressCheckoutButtonDataWithZeroValueCart(): void
    {
        $taxId = $this->createTaxId(Context::createDefaultContext());
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $productId = $this->getProductId($salesChannelContext->getContext(), $taxId, true);
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext);

        static::assertNull($expressCheckoutButtonData);
    }

    public function testGetExpressCheckoutButtonDataWithCustomer(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setActive(true);
        $country->setShippingAvailable(true);

        $activeBillingAddress = new CustomerAddressEntity();
        $activeBillingAddress->setCountry($country);

        $customerId = $this->getCustomerId();
        $taxId = $this->createTaxId(Context::createDefaultContext());
        $salesChannelContext = $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $customerId,
            ]
        );

        $productId = $this->getProductId($salesChannelContext->getContext(), $taxId);
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext);

        static::assertInstanceOf(CustomerEntity::class, $salesChannelContext->getCustomer());
        static::assertNull($expressCheckoutButtonData);
    }

    public function testGetExpressCheckoutButtonDataWithDoubleOptInGuestOrder(): void
    {
        $taxId = $this->createTaxId(Context::createDefaultContext());
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $productId = $this->getProductId($salesChannelContext->getContext(), $taxId);
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $this->systemConfigService->set('core.loginRegistration.doubleOptInGuestOrder', true);

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext);

        static::assertNull($expressCheckoutButtonData);
    }

    /**
     * @dataProvider dataProviderTestGetExpressCheckoutButtonDataWithCredentials
     */
    public function testGetExpressCheckoutButtonDataWithCredentials(bool $withSettingsLocale, bool $addToCart): void
    {
        $taxId = $this->createTaxId(Context::createDefaultContext());
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $productId = $this->getProductId($salesChannelContext->getContext(), $taxId);
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $this->systemConfigService->set(Settings::CLIENT_ID, self::CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'someClientSecret');

        if ($withSettingsLocale) {
            $this->systemConfigService->set(Settings::ECS_BUTTON_LANGUAGE_ISO, 'zz_ZZ');
        }

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext, $addToCart);

        static::assertNotNull($expressCheckoutButtonData);
        static::assertTrue($expressCheckoutButtonData->getProductDetailEnabled());
        static::assertTrue($expressCheckoutButtonData->getOffCanvasEnabled());
        static::assertTrue($expressCheckoutButtonData->getLoginEnabled());
        static::assertFalse($expressCheckoutButtonData->getListingEnabled());
        static::assertTrue($expressCheckoutButtonData->getCartEnabled());
        static::assertSame('gold', $expressCheckoutButtonData->getButtonColor());
        static::assertSame('rect', $expressCheckoutButtonData->getButtonShape());
        if ($withSettingsLocale) {
            static::assertSame('zz_ZZ', $expressCheckoutButtonData->getLanguageIso());
        } else {
            static::assertSame('en_GB', $expressCheckoutButtonData->getLanguageIso());
        }
        static::assertSame(self::CLIENT_ID, $expressCheckoutButtonData->getClientId());
        static::assertSame('EUR', $expressCheckoutButtonData->getCurrency());
        static::assertSame(\mb_strtolower(PaymentIntentV2::CAPTURE), $expressCheckoutButtonData->getIntent());
        static::assertSame($addToCart, $expressCheckoutButtonData->getAddProductToCart());
        static::assertSame('/paypal/express/create-order', $expressCheckoutButtonData->getCreateOrderUrl());
        static::assertSame('/paypal/express/prepare-checkout', $expressCheckoutButtonData->getPrepareCheckoutUrl());
        static::assertStringContainsString('/checkout/confirm', $expressCheckoutButtonData->getCheckoutConfirmUrl());
        static::assertSame('/paypal/express/prepare-cart', $expressCheckoutButtonData->getContextSwitchUrl());
        static::assertSame('/paypal/error', $expressCheckoutButtonData->getAddErrorUrl());
        static::assertSame($addToCart ? '/checkout/cart' : '/checkout/register', $expressCheckoutButtonData->getCancelRedirectUrl());
        static::assertTrue($expressCheckoutButtonData->isShowPayLater());
        static::assertNotNull($expressCheckoutButtonData->getPayPalPaymentMethodId());
        static::assertSame(
            $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            $expressCheckoutButtonData->getPayPalPaymentMethodId()
        );
    }

    public function dataProviderTestGetExpressCheckoutButtonDataWithCredentials(): array
    {
        return [
            [false, false],
            [true, false],
            [false, true],
            [true, true],
        ];
    }

    private function getProductId(Context $context, string $taxId, bool $priceZero = false): string
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'productNumber' => Uuid::randomHex(),
            'stock' => 1,
            'name' => 'test',
            'active' => true,
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => $priceZero ? 0 : 15, 'net' => $priceZero ? 0 : 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['id' => $taxId],
        ];

        $this->productRepository->create([$data], $context);

        return $id;
    }

    private function getCustomerId(): string
    {
        $id = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $id,
            'number' => 'wusel',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Wusel',
            'lastName' => 'Dusel',
            'customerNumber' => 'wusel',
            'email' => 'wuse@dusel.de',
            'password' => 'annanas1',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $id,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Wusel',
                    'lastName' => 'Dusel',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        return $id;
    }

    private function createTaxId(Context $context): string
    {
        /** @var EntityRepository $taxRepo */
        $taxRepo = $this->getContainer()->get(TaxDefinition::ENTITY_NAME . '.repository');
        $taxId = Uuid::randomHex();
        $taxData = [
            [
                'id' => $taxId,
                'taxRate' => 19.0,
                'name' => 'testTaxRate',
            ],
        ];

        $taxRepo->create($taxData, $context);

        return $taxId;
    }
}

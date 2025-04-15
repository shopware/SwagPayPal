<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
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
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCheckoutDataServiceInterface;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
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

    private PayLaterMethodData $payLaterMethodData;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $this->paymentMethodUtil = $container->get(PaymentMethodUtil::class);
        $this->salesChannelContextFactory = $container->get(SalesChannelContextFactory::class);
        $this->cartService = $container->get(CartService::class);
        $this->payLaterMethodData = $container->get(PayLaterMethodData::class);

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
            new CartPriceService(),
            $this->payLaterMethodData
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

    #[DataProvider('dataProviderTestGetExpressCheckoutButtonDataWithCredentials')]
    public function testGetExpressCheckoutButtonDataWithCredentials(bool $withSettingsLocale, bool $addToCart): void
    {
        $context = Context::createDefaultContext();
        $taxId = $this->createTaxId($context);
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $productId = $this->getProductId($salesChannelContext->getContext(), $taxId);
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $this->systemConfigService->set(Settings::CLIENT_ID, self::CLIENT_ID);
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'someClientSecret');

        if ($withSettingsLocale) {
            $this->systemConfigService->set(Settings::ECS_BUTTON_LANGUAGE_ISO, 'de_AT');
        }

        $salesChannelProductEntity = new SalesChannelProductEntity();
        $salesChannelProductEntity->setCalculatedPrice(new CalculatedPrice(
            2,
            2,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        $productPage = new ProductPage();
        $productPage->setProduct($salesChannelProductEntity);

        $event = new ProductPageLoadedEvent($productPage, $salesChannelContext, new Request());

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext, $addToCart, $event);

        static::assertNotNull($expressCheckoutButtonData);
        static::assertTrue($expressCheckoutButtonData->getProductDetailEnabled());
        static::assertTrue($expressCheckoutButtonData->getOffCanvasEnabled());
        static::assertTrue($expressCheckoutButtonData->getLoginEnabled());
        static::assertFalse($expressCheckoutButtonData->getListingEnabled());
        static::assertTrue($expressCheckoutButtonData->getCartEnabled());
        static::assertSame('gold', $expressCheckoutButtonData->getButtonColor());
        static::assertSame('sharp', $expressCheckoutButtonData->getButtonShape());
        if ($withSettingsLocale) {
            static::assertSame('de_DE', $expressCheckoutButtonData->getLanguageIso());
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
        static::assertSame('/paypal/handle-error', $expressCheckoutButtonData->getHandleErrorUrl());
        static::assertSame($addToCart ? '/checkout/cart' : '/checkout/register', $expressCheckoutButtonData->getCancelRedirectUrl());
        static::assertTrue($expressCheckoutButtonData->isShowPayLater());
        static::assertSame(['paypal', 'paylater', 'venmo'], $expressCheckoutButtonData->getFundingSources());
        static::assertNotNull($expressCheckoutButtonData->getPayPalPaymentMethodId());
        static::assertSame(
            $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            $expressCheckoutButtonData->getPayPalPaymentMethodId()
        );
    }

    #[DataProvider('dataProviderTestFundingSourcesWithPayLater')]
    public function testFundingSourcesWithPayLater(bool $showPayLaterSetting, bool $payLaterAvailable, array $expectedFundingSources): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $this->systemConfigService->set(Settings::ECS_SHOW_PAY_LATER, $showPayLaterSetting, $salesChannelContext->getSalesChannelId());

        $context = Context::createDefaultContext();
        $taxId = $this->createTaxId($context);
        $productId = $this->getProductId($context, $taxId);
        $product = new SalesChannelProductEntity();
        $product->setId($productId);
        $product->setPrice(new PriceCollection([
            new Price(Defaults::CURRENCY, 2, 5, false),
        ]));
        $product->setCalculatedPrice(new CalculatedPrice(
            2,
            2,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));

        $productPage = new ProductPage();
        $productPage->setProduct($product);

        $payLaterMethodData = $this->createMock(PayLaterMethodData::class);
        $payLaterMethodData->method('isAvailable')
            ->willReturn($payLaterAvailable);

        $payPalExpressCheckoutDataService = new PayPalExpressCheckoutDataService(
            $this->getContainer()->get(CartService::class),
            $this->getContainer()->get(LocaleCodeProvider::class),
            $this->getContainer()->get('router'),
            $this->paymentMethodUtil,
            $this->systemConfigService,
            new CredentialsUtil($this->systemConfigService),
            $this->getContainer()->get(CartPriceService::class),
            $payLaterMethodData
        );

        $event = new ProductPageLoadedEvent($productPage, $salesChannelContext, new Request());

        $expressCheckoutButtonData = $payPalExpressCheckoutDataService->buildExpressCheckoutButtonData($salesChannelContext, true, $event);

        static::assertNotNull($expressCheckoutButtonData);
        static::assertSame($expectedFundingSources, $expressCheckoutButtonData->getFundingSources());
        static::assertSame($payLaterAvailable && $showPayLaterSetting, $expressCheckoutButtonData->isShowPayLater());
    }

    public static function dataProviderTestFundingSourcesWithPayLater(): array
    {
        return [
            'PayLater disabled in settings' => [false, true, ['paypal', 'venmo']],
            'PayLater not available' => [true, false, ['paypal', 'venmo']],
            'PayLater enabled and available' => [true, true, ['paypal', 'paylater', 'venmo']],
            'PayLater disabled and not available' => [false, false, ['paypal', 'venmo']],
        ];
    }

    public static function dataProviderTestGetExpressCheckoutButtonDataWithCredentials(): array
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

<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;

class PayPalExpressCheckoutDataServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    private const CLIENT_ID = 'someClientId';

    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $payPalExpressCheckoutDataService;

    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();
        /** @var CartService $cartService */
        $cartService = $container->get(CartService::class);
        $this->cartService = $cartService;

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $container->get(SalesChannelContextFactory::class);
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->payPalExpressCheckoutDataService = new PayPalExpressCheckoutDataService(
            $this->cartService,
            $this->createLocaleCodeProvider()
        );
        /** @var EntityRepositoryInterface $productRepo */
        $productRepo = $container->get('product.repository');
        $this->productRepository = $productRepo;

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $container->get('customer.repository');
        $this->customerRepository = $customerRepo;
    }

    public function testGetExpressCheckoutButtonDataWithoutCart(): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);
        $expressCheckoutButtonData = $this->payPalExpressCheckoutDataService->getExpressCheckoutButtonData(
            $salesChannelContext,
            new SwagPayPalSettingStruct()
        );

        static::assertNull($expressCheckoutButtonData);
    }

    public function testGetExpressCheckoutButtonDataWithCustomer(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());

        $activeBillingAddress = new CustomerAddressEntity();
        $activeBillingAddress->setCountry($country);

        $customerId = $this->getCustomerId();
        $salesChannelContext = $this->salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $customerId,
            ]
        );

        $productId = $this->getProductId($salesChannelContext->getContext());
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $expressCheckoutButtonData = $this->payPalExpressCheckoutDataService->getExpressCheckoutButtonData(
            $salesChannelContext,
            new SwagPayPalSettingStruct()
        );

        static::assertInstanceOf(CustomerEntity::class, $salesChannelContext->getCustomer());
        static::assertNull($expressCheckoutButtonData);
    }

    /**
     * @dataProvider dataProviderTestGetExpressCheckoutButtonDataWithCredentials
     */
    public function testGetExpressCheckoutButtonDataWithCredentials(bool $withSettingsLocale): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);
        $productId = $this->getProductId($salesChannelContext->getContext());
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        $this->cartService->add($cart, $lineItem, $salesChannelContext);

        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId(self::CLIENT_ID);
        $settings->setClientSecret('someClientSecret');

        if ($withSettingsLocale) {
            $settings->setEcsButtonLanguageIso('zz_ZZ');
        }

        /** @var ExpressCheckoutButtonData $expressCheckoutButtonData */
        $expressCheckoutButtonData = $this->payPalExpressCheckoutDataService->getExpressCheckoutButtonData(
            $salesChannelContext,
            $settings
        );

        static::assertNotNull($expressCheckoutButtonData);
        static::assertTrue($expressCheckoutButtonData->getProductDetailEnabled());
        static::assertTrue($expressCheckoutButtonData->getOffCanvasEnabled());
        static::assertTrue($expressCheckoutButtonData->getLoginEnabled());
        static::assertTrue($expressCheckoutButtonData->getListingEnabled());
        static::assertTrue($expressCheckoutButtonData->getCartEnabled());
        static::assertFalse($expressCheckoutButtonData->getUseSandbox());
        static::assertSame('gold', $expressCheckoutButtonData->getButtonColor());
        static::assertSame('rect', $expressCheckoutButtonData->getButtonShape());
        if ($withSettingsLocale) {
            static::assertSame('zz_ZZ', $expressCheckoutButtonData->getLanguageIso());
        } else {
            static::assertSame('en_GB', $expressCheckoutButtonData->getLanguageIso());
        }
        static::assertSame(self::CLIENT_ID, $expressCheckoutButtonData->getClientId());
        static::assertSame('EUR', $expressCheckoutButtonData->getCurrency());
        static::assertSame(PaymentIntent::SALE, $expressCheckoutButtonData->getIntent());
        static::assertFalse($expressCheckoutButtonData->getAddProductToCart());
    }

    public function dataProviderTestGetExpressCheckoutButtonDataWithCredentials()
    {
        return [
            [
                false,
            ],
            [
                true,
            ],
        ];
    }

    private function getProductId(Context $context): string
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'productNumber' => Uuid::randomHex(),
            'stock' => 1,
            'name' => 'test',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
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
            'password' => 'annanas',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL,
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
}

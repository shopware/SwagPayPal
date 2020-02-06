<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Installment\Banner;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Installment\Banner\InstallmentBannerSubscriber;
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

class InstallmentBannerSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const CART_TOTAL_PRICE = 123.45;
    private const PRODUCT_PRICE = 678.9;
    private const ADVANCED_PRODUCT_PRICE = 111.22;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var string
     */
    private $payPalPaymentMethodId;

    /**
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->context = Context::createDefaultContext();
        $this->payPalPaymentMethodId = (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($this->context);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = InstallmentBannerSubscriber::getSubscribedEvents();

        static::assertCount(5, $events);
        foreach ($events as $event) {
            static::assertSame('addInstallmentBanner', $event);
        }
    }

    public function testAddInstallmentBannerPayPalNotInSalesChannel(): void
    {
        $subscriber = $this->createInstallmentBannerSubscriber();

        $event = $this->createCheckoutCartPageLoadedEvent(false);
        $subscriber->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerInvalidSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $subscriber = $this->createInstallmentBannerSubscriber($settings);

        $event = $this->createCheckoutCartPageLoadedEvent();
        $subscriber->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerDisabled(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');
        $settings->setInstallmentBannerEnabled(false);
        $subscriber = $this->createInstallmentBannerSubscriber($settings);

        $event = $this->createCheckoutCartPageLoadedEvent();
        $subscriber->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerCheckoutCart(): void
    {
        $subscriber = $this->createInstallmentBannerSubscriber();

        $event = $this->createCheckoutCartPageLoadedEvent();
        $subscriber->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(2, $extensions);

        /** @var BannerData|null $checkoutCartBannerData */
        $checkoutCartBannerData = $page->getExtension(
            InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID
        );
        static::assertInstanceOf(BannerData::class, $checkoutCartBannerData);
        static::assertSame(self::CART_TOTAL_PRICE, $checkoutCartBannerData->getAmount());
        static::assertSame('flex', $checkoutCartBannerData->getLayout());
        static::assertSame('grey', $checkoutCartBannerData->getColor());

        $this->assertBannerData($page, self::CART_TOTAL_PRICE);
    }

    public function testAddInstallmentBannerProductPage(): void
    {
        $subscriber = $this->createInstallmentBannerSubscriber();

        $event = $this->createProductPageLoadedEvent();
        $subscriber->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(1, $extensions);

        $this->assertBannerData($page, self::PRODUCT_PRICE);
    }

    public function testAddInstallmentBannerProductPageWithAdvancedPrices(): void
    {
        $subscriber = $this->createInstallmentBannerSubscriber();

        $event = $this->createProductPageLoadedEvent(true);
        $subscriber->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(1, $extensions);

        $this->assertBannerData($page, self::ADVANCED_PRODUCT_PRICE);
    }

    private function assertBannerData(Page $page, float $price): void
    {
        /** @var BannerData|null $bannerData */
        $bannerData = $page->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);
        static::assertInstanceOf(BannerData::class, $bannerData);
        static::assertSame($price, $bannerData->getAmount());
        static::assertSame('text', $bannerData->getLayout());
        static::assertSame('blue', $bannerData->getColor());
        static::assertSame('8x1', $bannerData->getRatio());
        static::assertSame('primary', $bannerData->getLogoType());
        static::assertSame('black', $bannerData->getTextColor());
    }

    private function createInstallmentBannerSubscriber(
        ?SwagPayPalSettingStruct $settings = null
    ): InstallmentBannerSubscriber {
        if ($settings === null) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('testClientId');
            $settings->setClientSecret('testClientSecret');
        }

        return new InstallmentBannerSubscriber(
            new SettingsServiceMock($settings),
            $this->paymentMethodUtil,
            new BannerDataService($this->paymentMethodUtil)
        );
    }

    private function createSalesChannelContext(bool $withPayPalInContext = true): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL
        );

        $paymentMethodsArray = [$salesChannelContext->getPaymentMethod()];
        if ($withPayPalInContext) {
            $payPalPaymentMethod = new PaymentMethodEntity();
            $payPalPaymentMethod->setId($this->payPalPaymentMethodId);
            $paymentMethodsArray[] = $payPalPaymentMethod;
            $this->addPayPalToDefaultsSalesChannel();
        }

        $salesChannelContext->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection($paymentMethodsArray));

        return $salesChannelContext;
    }

    private function createCheckoutCartPageLoadedEvent(bool $withPayPalInContext = true): CheckoutCartPageLoadedEvent
    {
        return new CheckoutCartPageLoadedEvent(
            $this->createCheckoutCartPage(),
            $this->createSalesChannelContext($withPayPalInContext),
            $this->createRequest()
        );
    }

    private function createCheckoutCartPage(): CheckoutCartPage
    {
        $page = new CheckoutCartPage();
        $cart = new Cart('test', 'testToken');
        $cart->setPrice(
            new CartPrice(
                0,
                self::CART_TOTAL_PRICE,
                0,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                CartPrice::TAX_STATE_GROSS
            )
        );
        $page->setCart($cart);

        return $page;
    }

    private function createProductPageLoadedEvent(bool $withAdvancedPrices = false): ProductPageLoadedEvent
    {
        return new ProductPageLoadedEvent(
            $this->createProductPage($withAdvancedPrices),
            $this->createSalesChannelContext(),
            $this->createRequest()
        );
    }

    private function createProductPage(bool $withAdvancedPrices = false): ProductPage
    {
        $page = new ProductPage();
        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice(
            new CalculatedPrice(
                self::PRODUCT_PRICE,
                self::PRODUCT_PRICE,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            )
        );
        $calculatedPrices = [];
        if ($withAdvancedPrices) {
            $calculatedPrices[] = new CalculatedPrice(
                self::ADVANCED_PRODUCT_PRICE,
                self::ADVANCED_PRODUCT_PRICE,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            );
        }
        $product->setCalculatedPrices(new PriceCollection($calculatedPrices));

        $page->setProduct($product);

        return $page;
    }

    private function createRequest(): Request
    {
        return new Request();
    }

    private function addPayPalToDefaultsSalesChannel(): void
    {
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepo->update([
            [
                'id' => Defaults::SALES_CHANNEL,
                'paymentMethods' => [
                    $this->payPalPaymentMethodId => [
                        'id' => $this->payPalPaymentMethodId,
                    ],
                ],
            ],
        ], $this->context);
    }
}

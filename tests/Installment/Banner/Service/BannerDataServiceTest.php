<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Installment\Banner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(BannerDataService::class)]
class BannerDataServiceTest extends TestCase
{
    private MockObject&LocaleCodeProvider $localeCodeProvider;

    private MockObject&PaymentMethodUtil $paymentMethodUtil;

    private SystemConfigServiceMock $systemConfigService;

    private MockObject&EntityRepository $languageRepository;

    private BannerDataService $bannerDataService;

    protected function setUp(): void
    {
        $this->localeCodeProvider = $this->createMock(LocaleCodeProvider::class);
        $this->systemConfigService = SystemConfigServiceMock::createWithCredentials([
            Settings::CROSS_BORDER_MESSAGING_ENABLED => true,
        ]);
        $this->paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $this->languageRepository = $this->createMock(EntityRepository::class);

        $this->bannerDataService = new BannerDataService(
            $this->localeCodeProvider,
            $this->systemConfigService,
            new CredentialsUtil($this->systemConfigService),
            $this->createMock(RouterInterface::class),
            $this->paymentMethodUtil,
            $this->languageRepository
        );
    }

    #[DataProvider('dataProviderCrossBorderBuyerCountry')]
    public function testCrossBorderBuyerCountry(string $isoLang, string $isoCurrency, ?string $expectedCountry): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage($isoLang, $isoCurrency);

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()), $salesChannelContext);

        static::assertSame($expectedCountry, $bannerData->getCrossBorderBuyerCountry());
    }

    public static function dataProviderCrossBorderBuyerCountry(): \Generator
    {
        yield 'valid -> en-GB, GBP' => ['en-GB', 'GBP', 'UK'];
        yield 'valid -> en-US, USD' => ['en-US', 'USD', 'US'];
        yield 'valid -> de-DE, EUR' => ['de-DE', 'EUR', 'DE'];
        yield 'valid -> es-ES, EUR' => ['es-ES', 'EUR', 'ES'];
        yield 'valid -> fr-FR, EUR' => ['fr-FR', 'EUR', 'FR'];
        yield 'valid -> it-IT, EUR' => ['it-IT', 'EUR', 'IT'];
        yield 'valid -> fallback en-GB, GBP' => ['es-ES', 'GBP', 'UK'];

        yield 'invalid -> de-DE, GBP' => ['de-DE', 'USD', null];
    }

    public function testCrossBorderBuyerCountryOverride(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage('en-GB', 'EUR');

        $this->systemConfigService->set(Settings::CROSS_BORDER_BUYER_COUNTRY, 'de-DE');

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()), $salesChannelContext);

        static::assertSame('DE', $bannerData->getCrossBorderBuyerCountry());
    }

    public function testCrossBorderBuyerCountryDisabled(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $this->systemConfigService->set(Settings::CROSS_BORDER_MESSAGING_ENABLED, false);

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()), $salesChannelContext);

        static::assertNull($bannerData->getCrossBorderBuyerCountry());
    }

    public function testBannerAppearanceDefaults(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame(BannerData::LOGO_TYPE_WORDMARK, $bannerData->getLogoType());
        static::assertSame('monochrome', $bannerData->getTextColor());
        static::assertSame(12, $bannerData->getTextSize());
    }

    public function testLogoTypeIsReadFromConfig(): void
    {
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_LOGO_TYPE, 'alternative');
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame(BannerData::LOGO_TYPE_MONOGRAM, $bannerData->getLogoType());
    }

    public function testTextColorIsReadFromConfig(): void
    {
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_TEXT_COLOR, 'white');
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame('white', $bannerData->getTextColor());
    }

    public function testGrayscaleTextColorIsMappedToMonochrome(): void
    {
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_TEXT_COLOR, 'grayscale');
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame(BannerData::TEXT_COLOR_MONOCHROME, $bannerData->getTextColor());
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed, the SDK v6 values are then the only ones
     */
    public function testBannerAppearanceKeepsUnmappedValuesWithSdkV6Disabled(): void
    {
        $this->systemConfigService->set(Settings::SDK_V6_ENABLED, false);
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_LOGO_TYPE, 'alternative');
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_TEXT_COLOR, 'grayscale');
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame('alternative', $bannerData->getLogoType());
        static::assertSame('grayscale', $bannerData->getTextColor());
    }

    public function testTextSizeIsReadFromConfig(): void
    {
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_TEXT_SIZE, 16);
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame(16, $bannerData->getTextSize());
    }

    public function testTextSizeCastToIntWhenStoredAsString(): void
    {
        $this->systemConfigService->set(Settings::INSTALLMENT_BANNER_TEXT_SIZE, '16');
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            new FooterPagelet(null, new CategoryCollection(), new PaymentMethodCollection(), new ShippingMethodCollection()),
            $salesChannelContext,
        );

        static::assertSame(16, $bannerData->getTextSize());
    }

    private function createSalesChannelContextWithLanguage(string $isoLang = 'en-GB', string $isoCurrency = 'GBP'): SalesChannelContext
    {
        $context = new Context(
            new SystemSource(),
            languageIdChain: [$isoLang, 'en-GB'],
        );

        $salesChannelContext = Generator::generateSalesChannelContext($context);
        $salesChannelContext->getCurrency()->setIsoCode($isoCurrency);

        $this->languageRepository
            ->method('search')
            ->willReturnCallback(static fn (Criteria $criteria) => new EntitySearchResult(
                'language',
                1,
                new EntityCollection(
                    \array_map(static fn ($id) => (new LanguageEntity())->assign([
                        'id' => $id,
                        'locale' => (new LocaleEntity())->assign(['code' => $id]),
                    ]), $criteria->getIds()),
                ),
                null,
                $criteria,
                $context,
            ));

        return $salesChannelContext;
    }
}

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
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(BannerDataService::class)]
class BannerDataServiceTest extends TestCase
{
    private MockObject&PaymentMethodUtil $paymentMethodUtil;

    private SystemConfigServiceMock $systemConfigService;

    private MockObject&EntityRepository $languageRepository;

    private BannerDataService $bannerDataService;

    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithCredentials([
            Settings::CROSS_BORDER_MESSAGING_ENABLED => true,
        ]);
        $this->paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $this->languageRepository = $this->createMock(EntityRepository::class);

        $this->bannerDataService = new BannerDataService(
            $this->paymentMethodUtil,
            new CredentialsUtil($this->systemConfigService),
            $this->systemConfigService,
            $this->languageRepository
        );
    }

    #[DataProvider('dataProviderCrossBorderBuyerCountry')]
    public function testCrossBorderBuyerCountry(string $isoLang, string $isoCurrency, ?string $expectedCountry): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage($isoLang, $isoCurrency);

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null), $salesChannelContext);

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

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null), $salesChannelContext);

        static::assertSame('DE', $bannerData->getCrossBorderBuyerCountry());
    }

    public function testCrossBorderBuyerCountryDisabled(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLanguage();

        $this->systemConfigService->set(Settings::CROSS_BORDER_MESSAGING_ENABLED, false);

        $bannerData = $this->bannerDataService->getInstallmentBannerData(new FooterPagelet(null), $salesChannelContext);

        static::assertNull($bannerData->getCrossBorderBuyerCountry());
    }

    private function createSalesChannelContextWithLanguage(string $isoLang = 'en-GB', string $isoCurrency = 'GBP'): SalesChannelContext
    {
        $context = new Context(
            new SystemSource(),
            languageIdChain: [$isoLang, 'en-GB'],
        );

        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getCurrency()->setIsoCode($isoCurrency);

        $this->languageRepository
            ->method('search')
            ->willReturnCallback(fn (Criteria $criteria) => new EntitySearchResult(
                'language',
                1,
                new EntityCollection(
                    \array_map(fn ($id) => (new LanguageEntity())->assign([
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

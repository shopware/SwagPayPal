<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\AbstractScriptDataService;
use Swag\PayPal\Storefront\Data\Struct\AbstractScriptData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class BannerDataService extends AbstractScriptDataService implements BannerDataServiceInterface
{
    /**
     * @internal
     *
     * @param EntityRepository<LanguageCollection> $languageRepository
     */
    public function __construct(
        LocaleCodeProvider $localeCodeProvider,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        RouterInterface $router,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $languageRepository,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil, $router);
    }

    /**
     * @param CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage|FooterPagelet|QuickviewPagelet $page
     */
    public function getInstallmentBannerData(
        $page,
        SalesChannelContext $salesChannelContext,
    ): BannerData {
        $amount = 0.0;

        if ($page instanceof CheckoutCartPage
            || $page instanceof CheckoutConfirmPage
            || $page instanceof CheckoutRegisterPage
            || $page instanceof OffcanvasCartPage
        ) {
            $amount = $page->getCart()->getPrice()->getTotalPrice();
        }

        if ($page instanceof ProductPage) {
            $product = $page->getProduct();

            $amount = $product->getCalculatedPrice()->getUnitPrice();

            $firstCalculatedPrice = $product->getCalculatedPrices()->first();
            if ($firstCalculatedPrice !== null) {
                $amount = $firstCalculatedPrice->getUnitPrice();
            }
        }

        $bannerData = new BannerData();
        $salesChannelId = $salesChannelContext->getSalesChannelId();

        if ($this->systemConfigService->getBool(Settings::CROSS_BORDER_MESSAGING_ENABLED, $salesChannelId)) {
            $crossBorderBuyerCountry = $this->matchBuyerCountry($this->systemConfigService->getString(Settings::CROSS_BORDER_BUYER_COUNTRY, $salesChannelId), $salesChannelContext);
            $crossBorderBuyerCountry ??= $this->determineBuyerCountry($salesChannelContext);
        }

        $bannerData->assign([
            ...$this->getBaseData($salesChannelContext),
            'paymentMethodId' => (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            'amount' => $amount,
            'footerEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_FOOTER_ENABLED, $salesChannelId),
            'cartEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_CART_ENABLED, $salesChannelId),
            'offCanvasCartEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED, $salesChannelId),
            'loginPageEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED, $salesChannelId),
            'detailPageEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED, $salesChannelId),
            'logoType' => $this->systemConfigService->getString(Settings::INSTALLMENT_BANNER_LOGO_TYPE, $salesChannelId),
            'textColor' => $this->systemConfigService->getString(Settings::INSTALLMENT_BANNER_TEXT_COLOR, $salesChannelId),
            'textSize' => $this->systemConfigService->getInt(Settings::INSTALLMENT_BANNER_TEXT_SIZE, $salesChannelId) ?: 12,
            'crossBorderBuyerCountry' => $crossBorderBuyerCountry ?? null,
            'pageType' => $this->getPageType($page),
        ]);

        return $bannerData;
    }

    private function determineBuyerCountry(SalesChannelContext $salesChannelContext): ?string
    {
        $languages = $this->languageRepository->search(
            (new Criteria($salesChannelContext->getLanguageIdChain()))->addAssociation('locale'),
            $salesChannelContext->getContext()
        );

        return $languages->getEntities()->reduce(
            fn (?string $languageCode, LanguageEntity $language) => $languageCode ?? $this->matchBuyerCountry(
                $language->getLocale()?->getCode() ?? 'en-GB',
                $salesChannelContext,
            ),
        );
    }

    private function matchBuyerCountry(string $isoCode, SalesChannelContext $salesChannelContext): ?string
    {
        $key = \sprintf(
            '%s-%s',
            $isoCode,
            $salesChannelContext->getCurrency()->getIsoCode(),
        );

        return match ($key) {
            'en-AU-AUD' => 'AU',
            'de-DE-EUR' => 'DE',
            'es-ES-EUR' => 'ES',
            'fr-FR-EUR' => 'FR',
            'it-IT-EUR' => 'IT',
            'en-GB-GBP' => 'UK',
            'en-US-USD' => 'US',
            default => null,
        };
    }

    /**
     * @param CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage|FooterPagelet|QuickviewPagelet $page
     */
    private function getPageType($page): ?string
    {
        return match (true) {
            $page instanceof CheckoutCartPage => AbstractScriptData::PAGE_TYPE_CART,
            $page instanceof CheckoutRegisterPage,
            $page instanceof CheckoutConfirmPage => AbstractScriptData::PAGE_TYPE_CHECKOUT,
            $page instanceof OffcanvasCartPage => AbstractScriptData::PAGE_TYPE_MINI_CART,
            $page instanceof ProductPage => AbstractScriptData::PAGE_TYPE_PRODUCT_DETAILS,
            default => null,
        };
    }
}

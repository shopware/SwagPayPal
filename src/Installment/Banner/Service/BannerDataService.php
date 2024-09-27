<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
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
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;

#[Package('checkout')]
class BannerDataService implements BannerDataServiceInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly CredentialsUtilInterface $credentialsUtil,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $languageRepository,
    ) {
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

        $merchantPayerId = $this->credentialsUtil->getMerchantPayerId($salesChannelContext->getSalesChannelId());

        if ($this->systemConfigService->getBool(Settings::CROSS_BORDER_MESSAGING_ENABLED)) {
            $crossBorderBuyerCountry = $this->matchBuyerCountry($this->systemConfigService->getString(Settings::CROSS_BORDER_BUYER_COUNTRY), $salesChannelContext);
            $crossBorderBuyerCountry ??= $this->determineBuyerCountry($salesChannelContext);
        }

        $bannerData->assign([
            'paymentMethodId' => (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            'clientId' => $this->credentialsUtil->getClientId($salesChannelContext->getSalesChannelId()),
            'amount' => $amount,
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'merchantPayerId' => $merchantPayerId,
            'partnerAttributionId' => $merchantPayerId ? PartnerAttributionId::PAYPAL_PPCP : PartnerAttributionId::PAYPAL_CLASSIC,
            'footerEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_FOOTER_ENABLED),
            'cartEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_CART_ENABLED),
            'offCanvasCartEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED),
            'loginPageEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED),
            'detailPageEnabled' => $this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED),
            'crossBorderBuyerCountry' => $crossBorderBuyerCountry ?? null,
        ]);

        return $bannerData;
    }

    private function determineBuyerCountry(SalesChannelContext $salesChannelContext): ?string
    {
        /** @var EntitySearchResult<LanguageCollection> $languages */
        $languages = $this->languageRepository->search(
            (new Criteria($salesChannelContext->getLanguageIdChain()))->addAssociation('locale'),
            $salesChannelContext->getContext()
        );

        return $languages->reduce(
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
}

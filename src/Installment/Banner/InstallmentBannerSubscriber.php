<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Installment\Banner\Service\BannerDataServiceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\Availability\AvailabilityContextBuilder;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class InstallmentBannerSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID = 'payPalInstallmentBannerData';
    public const PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID = 'payPalInstallmentBannerDataCheckoutCart';

    public function __construct(
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly BannerDataServiceInterface $bannerDataService,
        private readonly ExcludedProductValidator $excludedProductValidator,
        private readonly LoggerInterface $logger,
        private readonly PayLaterMethodData $payLaterMethodData,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addInstallmentBanner',
            CheckoutConfirmPageLoadedEvent::class => 'addInstallmentBanner',
            CheckoutRegisterPageLoadedEvent::class => 'addInstallmentBanner',
            OffcanvasCartPageLoadedEvent::class => 'addInstallmentBanner',
            ProductPageLoadedEvent::class => 'addInstallmentBanner',

            FooterPageletLoadedEvent::class => 'addInstallmentBannerPagelet',
            QuickviewPageletLoadedEvent::class => 'addInstallmentBannerPagelet',
        ];
    }

    public function addInstallmentBanner(PageLoadedEvent $pageLoadedEvent): void
    {
        $salesChannelContext = $pageLoadedEvent->getSalesChannelContext();
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext) === false) {
            return;
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        /** @var CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage $page */
        $page = $pageLoadedEvent->getPage();

        if ($page instanceof ProductPage) {
            if ($this->excludedProductValidator->isProductExcluded($page->getProduct(), $pageLoadedEvent->getSalesChannelContext())) {
                return;
            }

            $availabilityContext = AvailabilityContextBuilder::buildFromProduct($page->getProduct(), $salesChannelContext);
        }

        if (!$page instanceof ProductPage) {
            if ($this->excludedProductValidator->cartContainsExcludedProduct($page->getCart(), $pageLoadedEvent->getSalesChannelContext())) {
                return;
            }

            $availabilityContext = AvailabilityContextBuilder::buildFromCart($page->getCart(), $salesChannelContext);
        }

        if (!$this->shouldDisplayPayLaterBanner($page, $availabilityContext)) {
            return;
        }

        $bannerData = $this->bannerDataService->getInstallmentBannerData($page, $salesChannelContext);

        if ($page instanceof CheckoutCartPage) {
            $productTableBannerData = clone $bannerData;
            $productTableBannerData->setLayout('flex');
            $productTableBannerData->setColor('grey');
            $productTableBannerData->setRatio('20x1');

            $page->addExtension(self::PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID, $productTableBannerData);
        }

        $page->addExtension(
            self::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID,
            $bannerData
        );

        $this->logger->debug('Added data to {page}', ['page' => $pageLoadedEvent::class]);
    }

    public function addInstallmentBannerPagelet(PageletLoadedEvent $pageletLoadedEvent): void
    {
        $salesChannelContext = $pageletLoadedEvent->getSalesChannelContext();
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext) === false) {
            return;
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($pageletLoadedEvent instanceof QuickviewPageletLoadedEvent
            && $this->excludedProductValidator->isProductExcluded($pageletLoadedEvent->getPagelet()->getProduct(), $pageletLoadedEvent->getSalesChannelContext())) {
            return;
        }

        /** @var FooterPagelet|QuickviewPagelet $pagelet */
        $pagelet = $pageletLoadedEvent->getPagelet();

        $bannerData = $this->bannerDataService->getInstallmentBannerData($pagelet, $salesChannelContext);

        $pagelet->addExtension(
            self::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID,
            $bannerData
        );
    }

    private function shouldDisplayPayLaterBanner(Page $page, AvailabilityContext $availabilityContext): bool
    {
        return $this->pageOfCorrectType($page)
            ? $this->payLaterMethodData->isAvailable($availabilityContext)
            : true;
    }

    private function pageOfCorrectType(Page $page): bool
    {
        return $page instanceof CheckoutRegisterPage
            || $page instanceof OffcanvasCartPage
            || $page instanceof CheckoutConfirmPage;
    }
}

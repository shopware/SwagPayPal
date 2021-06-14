<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Installment\Banner\Service\BannerDataServiceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InstallmentBannerSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID = 'payPalInstallmentBannerData';
    public const PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID = 'payPalInstallmentBannerDataCheckoutCart';

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private PaymentMethodUtil $paymentMethodUtil;

    private BannerDataServiceInterface $bannerDataService;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        PaymentMethodUtil $paymentMethodUtil,
        BannerDataServiceInterface $bannerDataService,
        LoggerInterface $logger
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->bannerDataService = $bannerDataService;
        $this->logger = $logger;
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

        if (!$this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_ENABLED)) {
            return;
        }

        /** @var CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage $page */
        $page = $pageLoadedEvent->getPage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData($page, $salesChannelContext);

        if ($page instanceof CheckoutCartPage) {
            $productTableBannerData = new BannerData(
                $bannerData->getPaymentMethodId(),
                $bannerData->getClientId(),
                $bannerData->getAmount(),
                $bannerData->getCurrency(),
                'flex',
                'grey',
                '20x1'
            );

            $page->addExtension(self::PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID, $productTableBannerData);
        }

        $page->addExtension(
            self::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID,
            $bannerData
        );

        $this->logger->debug('Added data to {page}', ['page' => \get_class($pageLoadedEvent)]);
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

        if (!$this->systemConfigService->getBool(Settings::INSTALLMENT_BANNER_ENABLED, $salesChannelContext->getSalesChannelId())) {
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
}

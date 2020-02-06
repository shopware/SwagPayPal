<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner;

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
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InstallmentBannerSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID = 'payPalInstallmentBannerData';
    public const PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID = 'payPalInstallmentBannerDataCheckoutCart';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var BannerDataService
     */
    private $bannerDataService;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PaymentMethodUtil $paymentMethodUtil,
        BannerDataService $bannerDataService
    ) {
        $this->settingsService = $settingsService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->bannerDataService = $bannerDataService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addInstallmentBanner',
            CheckoutConfirmPageLoadedEvent::class => 'addInstallmentBanner',
            CheckoutRegisterPageLoadedEvent::class => 'addInstallmentBanner',
            OffcanvasCartPageLoadedEvent::class => 'addInstallmentBanner',
            ProductPageLoadedEvent::class => 'addInstallmentBanner',
        ];
    }

    public function addInstallmentBanner(PageLoadedEvent $pageLoadedEvent): void
    {
        $salesChannelContext = $pageLoadedEvent->getSalesChannelContext();
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext) === false) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($settings->getInstallmentBannerEnabled() === false) {
            return;
        }

        /** @var CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage $page */
        $page = $pageLoadedEvent->getPage();

        $bannerData = $this->bannerDataService->getInstallmentBannerData(
            $page,
            $salesChannelContext,
            $settings
        );

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
    }
}

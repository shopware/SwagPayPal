<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID = 'payPalEcsButtonData';

    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $expressCheckoutDataService;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(
        PayPalExpressCheckoutDataService $service,
        SettingsServiceInterface $settingsService,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->expressCheckoutDataService = $service;
        $this->settingsService = $settingsService;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
        ];
    }

    public function addExpressCheckoutDataToPage(PageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext) === false) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($this->expressOptionForEventEnabled($settings, $event) === false) {
            return;
        }

        if ($event instanceof ProductPageLoadedEvent || $event instanceof NavigationPageLoadedEvent) {
            $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData(
                $salesChannelContext,
                $settings,
                true
            );
        } else {
            $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData(
                $salesChannelContext,
                $settings
            );
        }

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $event->getPage()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    public function addExpressCheckoutDataToPagelet(QuickviewPageletLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext) === false) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($this->expressOptionForEventEnabled($settings, $event) === false) {
            return;
        }

        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData(
            $salesChannelContext,
            $settings,
            true
        );

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $quickviewPagelet = $event->getPagelet();
        $quickviewPagelet->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    private function expressOptionForEventEnabled(SwagPayPalSettingStruct $settings, ShopwareEvent $event): bool
    {
        switch (\get_class($event)) {
            case ProductPageLoadedEvent::class:
            case QuickviewPageletLoadedEvent::class:
                return $settings->getEcsDetailEnabled();
            case OffcanvasCartPageLoadedEvent::class:
                return $settings->getEcsOffCanvasEnabled();
            case CheckoutRegisterPageLoadedEvent::class:
                return $settings->getEcsLoginEnabled();
            case CheckoutCartPageLoadedEvent::class:
                return $settings->getEcsCartEnabled();
            case NavigationPageLoadedEvent::class:
                return $settings->getEcsListingEnabled();
            default:
                return false;
        }
    }
}

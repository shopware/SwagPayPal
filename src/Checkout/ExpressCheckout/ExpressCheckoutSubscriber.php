<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Event\NavigationEvents;
use Shopware\Storefront\Event\ProductEvents;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $expressCheckoutDataService;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    public function __construct(
        PayPalExpressCheckoutDataService $service,
        SettingsServiceInterface $settingsService
    ) {
        $this->expressCheckoutDataService = $service;
        $this->settingsService = $settingsService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutEvents::CHECKOUT_OFFCANVAS_CART_PAGE_LOADED_EVENT => 'addExpressCheckoutDataToPage',
            CheckoutEvents::CHECKOUT_REGISTER_PAGE_LOADED_EVENT => 'addExpressCheckoutDataToPage',
            CheckoutEvents::CHECKOUT_CART_PAGE_LOADED_EVENT => 'addExpressCheckoutDataToPage',
            ProductEvents::PRODUCT_PAGE_LOADED_EVENT => 'addExpressCheckoutDataToPage',
            NavigationEvents::NAVIGATION_PAGE_LOADED_EVENT => 'addExpressCheckoutDataToPage',
        ];
    }

    /**
     * @param NavigationPageLoadedEvent|ProductPageLoadedEvent|OffcanvasCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function addExpressCheckoutDataToPage($event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$this->expressOptionForEventEnabled($settings, $event)) {
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

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }

    /**
     * @param NavigationPageLoadedEvent|ProductPageLoadedEvent|OffcanvasCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     */
    private function expressOptionForEventEnabled(SwagPayPalSettingStruct $settings, $event): bool
    {
        switch ($event->getName()) {
            case ProductEvents::PRODUCT_PAGE_LOADED_EVENT:
                return $settings->getEcsDetailEnabled();
            case CheckoutEvents::CHECKOUT_OFFCANVAS_CART_PAGE_LOADED_EVENT:
                return $settings->getEcsOffCanvasEnabled();
            case CheckoutEvents::CHECKOUT_REGISTER_PAGE_LOADED_EVENT:
                return $settings->getEcsLoginEnabled();
            case CheckoutEvents::CHECKOUT_CART_PAGE_LOADED_EVENT:
                return $settings->getEcsCartEnabled();
            case NavigationEvents::NAVIGATION_PAGE_LOADED_EVENT:
                return $settings->getEcsListingEnabled();
            default:
                return false;
        }
    }
}

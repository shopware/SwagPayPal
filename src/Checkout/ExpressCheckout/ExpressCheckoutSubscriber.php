<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $expressCheckoutDataService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var SettingsService
     */
    private $settingsService;

    public function __construct(
        PayPalExpressCheckoutDataService $service,
        CartService $cartService,
        SettingsService $settingsService
    ) {
        $this->expressCheckoutDataService = $service;
        $this->cartService = $cartService;
        $this->settingsService = $settingsService;
    }

    public static function getSubscribedEvents()
    {
        return [
            OffcanvasCartPageLoadedEvent::NAME => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::NAME => 'addExpressCheckoutDataToPage',
            CheckoutCartPageLoadedEvent::NAME => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::NAME => 'addExpressCheckoutDataToPage',
        ];
    }

    /**
     * @param ProductPageLoadedEvent|OffcanvasCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     */
    public function addExpressCheckoutDataToPage($event): void
    {
        $settings = $this->settingsService->getSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
        if (!$this->expressOptionForEventEnabled($settings, $event)) {
            return;
        }

        if ($event instanceof ProductPageLoadedEvent) {
            $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext(), $settings, true);
        } else {
            $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext(), $settings);
        }

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }

    /**
     * @param ProductPageLoadedEvent|OffcanvasCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     */
    private function expressOptionForEventEnabled(SwagPayPalSettingGeneralStruct $settings, $event): bool
    {
        switch ($event->getName()) {
            case ProductPageLoadedEvent::NAME:
                return $settings->getEcsDetailEnabled();
            case OffcanvasCartPageLoadedEvent::NAME:
                return $settings->getEcsOffCanvasEnabled();
            case CheckoutRegisterPageLoadedEvent::NAME:
                return $settings->getEcsLoginEnabled();
            case CheckoutCartPageLoadedEvent::NAME:
                return $settings->getEcsCartEnabled();
            default:
                return false;
        }
    }
}

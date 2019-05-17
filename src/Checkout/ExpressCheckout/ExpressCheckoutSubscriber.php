<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Pagelet\Checkout\AjaxCart\CheckoutAjaxCartPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $expressCheckoutDataService;

    public function __construct(PayPalExpressCheckoutDataService $service)
    {
        $this->expressCheckoutDataService = $service;
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckoutAjaxCartPageletLoadedEvent::NAME => 'onAjaxCartLoaded',
            CheckoutRegisterPageLoadedEvent::NAME => 'onCheckoutRegisterLoaded',
            CheckoutCartPageLoadedEvent::NAME => 'onCheckoutCartLoaded',
        ];
    }

    public function onAjaxCartLoaded(CheckoutAjaxCartPageletLoadedEvent $event): void
    {
        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext());

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPagelet()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }

    public function onCheckoutRegisterLoaded(CheckoutRegisterPageLoadedEvent $event): void
    {
        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext());

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }

    public function onCheckoutCartLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext());

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }
}

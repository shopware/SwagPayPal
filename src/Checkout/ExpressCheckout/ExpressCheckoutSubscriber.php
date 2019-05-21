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
            CheckoutAjaxCartPageletLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
            CheckoutRegisterPageLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
            CheckoutCartPageLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
        ];
    }

    /**
     * @param CheckoutAjaxCartPageletLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     */
    public function addExpressCheckoutDataToCart($event): void
    {
        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext());

        if (!$expressCheckoutButtonData) {
            return;
        }

        if ($event instanceof CheckoutAjaxCartPageletLoadedEvent) {
            $event->getPagelet()->addExtension('payPalExpressData', $expressCheckoutButtonData);

            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }
}

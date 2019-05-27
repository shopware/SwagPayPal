<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
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
            OffcanvasCartPageLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
            CheckoutRegisterPageLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
            CheckoutCartPageLoadedEvent::NAME => 'addExpressCheckoutDataToCart',
        ];
    }

    /**
     * @param OffcanvasCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|CheckoutCartPageLoadedEvent $event
     */
    public function addExpressCheckoutDataToCart($event): void
    {
        $expressCheckoutButtonData = $this->expressCheckoutDataService->getExpressCheckoutButtonData($event->getSalesChannelContext());

        if (!$expressCheckoutButtonData) {
            return;
        }

        $event->getPage()->addExtension('payPalExpressData', $expressCheckoutButtonData);
    }
}

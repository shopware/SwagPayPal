<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SPBCheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var SPBCheckoutDataService
     */
    private $spbCheckoutDataService;

    public function __construct(SPBCheckoutDataService $spbCheckoutDataService)
    {
        $this->spbCheckoutDataService = $spbCheckoutDataService;
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckoutConfirmPageLoadedEvent::NAME => 'onCheckoutConfirmLoaded',
        ];
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $buttonData = $this->spbCheckoutDataService->getCheckoutData($event->getPage());

        if (!$buttonData) {
            return;
        }

        $event->getPage()->addExtension('spbCheckoutButtonData', $buttonData);
    }
}

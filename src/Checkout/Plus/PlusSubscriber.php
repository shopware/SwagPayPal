<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlusSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PlusDataService
     */
    private $plusDataService;

    public function __construct(SettingsServiceInterface $settingsService, PlusDataService $plusDataService)
    {
        $this->settingsService = $settingsService;
        $this->plusDataService = $plusDataService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutEvents::CHECKOUT_CONFIRM_PAGE_LOADED_EVENT => 'onCheckoutConfirmLoaded',
        ];
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPayPalPlusEnabled()) {
            return;
        }

        $plusData = $this->plusDataService->getPlusData($event->getPage()->getCart(), $salesChannelContext, $settings);

        if ($plusData === null) {
            return;
        }

        $event->getPage()->addExtension('payPalPlusData', $plusData);
    }
}

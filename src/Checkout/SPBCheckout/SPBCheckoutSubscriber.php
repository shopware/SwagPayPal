<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SPBCheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var SPBCheckoutDataService
     */
    private $spbCheckoutDataService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(
        SettingsServiceInterface $settingsService,
        SPBCheckoutDataService $spbCheckoutDataService,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->settingsService = $settingsService;
        $this->spbCheckoutDataService = $spbCheckoutDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->paymentMethodUtil->getPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getSpbCheckoutEnabled() || $settings->getMerchantCountry() === SwagPayPalSettingStruct::MERCHANT_COUNTRY_GERMANY) {
            return;
        }

        $buttonData = $this->spbCheckoutDataService->getCheckoutData(
            $event->getSalesChannelContext(),
            $settings
        );

        $event->getPage()->addExtension('spbCheckoutButtonData', $buttonData);
    }
}

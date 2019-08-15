<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SPBMarksSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID = 'payPalSpbMarksData';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(SettingsServiceInterface $settingsService, PaymentMethodUtil $paymentMethodUtil)
    {
        $this->settingsService = $settingsService;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountPaymentMethodPageLoadedEvent::class => 'onAccountPaymentMethodPageLoaded',
            FooterPageletLoadedEvent::class => 'onFooterPageletLoaded',
        ];
    }

    public function onAccountPaymentMethodPageLoaded(AccountPaymentMethodPageLoadedEvent $event): void
    {
        $spbMarksData = $this->getSpbMarksData($event->getSalesChannelContext());
        if ($spbMarksData === null) {
            return;
        }

        $event->getPage()->addExtension(self::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID, $spbMarksData);
    }

    public function onFooterPageletLoaded(FooterPageletLoadedEvent $event): void
    {
        $spbMarksData = $this->getSpbMarksData($event->getSalesChannelContext());
        if ($spbMarksData === null) {
            return;
        }

        $event->getPagelet()->addExtension(self::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID, $spbMarksData);
    }

    private function getSpbMarksData(SalesChannelContext $salesChannelContext): ?SPBMarksData
    {
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return null;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if (!$settings->getSpbCheckoutEnabled()) {
            return null;
        }

        return new SPBMarksData(
            $settings->getClientId(),
            (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext())
        );
    }
}

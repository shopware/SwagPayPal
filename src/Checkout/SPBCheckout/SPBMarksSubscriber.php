<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SPBMarksSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID = 'payPalSpbMarksData';

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private PaymentMethodUtil $paymentMethodUtil;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        PaymentMethodUtil $paymentMethodUtil,
        LoggerInterface $logger
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'addMarksExtension',
            AccountPaymentMethodPageLoadedEvent::class => 'addMarksExtension',
            FooterPageletLoadedEvent::class => 'addMarksExtension',
            CheckoutConfirmPageLoadedEvent::class => 'addMarksExtension',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|FooterPageletLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function addMarksExtension($event): void
    {
        $spbMarksData = $this->getSpbMarksData($event->getSalesChannelContext());
        if ($spbMarksData === null) {
            return;
        }

        $this->logger->debug('Adding SPB marks to {page}', ['page' => \get_class($event)]);
        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $confirmPage = $event->getPage();
            if ($confirmPage->getCart()->getExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID) !== null) {
                return;
            }

            $confirmPage->addExtension(self::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID, $spbMarksData);

            return;
        }

        if ($event instanceof AccountPaymentMethodPageLoadedEvent || $event instanceof AccountEditOrderPageLoadedEvent) {
            $event->getPage()->addExtension(self::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID, $spbMarksData);

            return;
        }

        $event->getPagelet()->addExtension(self::PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID, $spbMarksData);
    }

    private function getSpbMarksData(SalesChannelContext $salesChannelContext): ?SPBMarksData
    {
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return null;
        }

        $salesChannelId = $salesChannelContext->getSalesChannelId();

        try {
            $this->settingsValidationService->validate($salesChannelId);
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if (!$this->systemConfigService->getBool(Settings::SPB_CHECKOUT_ENABLED, $salesChannelId)
            || $this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_GERMANY
        ) {
            return null;
        }

        $clientId = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId)
            ? $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId)
            : $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);

        return new SPBMarksData(
            $clientId,
            (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            $this->systemConfigService->getBool(Settings::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED, $salesChannelId)
        );
    }
}

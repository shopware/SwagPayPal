<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SettingsValidationServiceInterface $settingsValidationService;

    private PaymentMethodUtil $paymentMethodUtil;

    private LoggerInterface $logger;

    private CartPriceService $cartPriceService;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        PaymentMethodUtil $paymentMethodUtil,
        LoggerInterface $logger,
        CartPriceService $cartPriceService
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->logger = $logger;
        $this->cartPriceService = $cartPriceService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $this->checkForMissingSettings($event);
        $this->checkForCartValue($event);
    }

    private function checkForMissingSettings(CheckoutConfirmPageLoadedEvent $event): void
    {
        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            $this->logger->info('PayPal is removed from the available Payment Methods: {message}', ['message' => $e->getMessage()]);
            $this->removePayPalPaymentMethodFromConfirmPage($event);
        }
    }

    private function checkForCartValue(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($this->cartPriceService->isZeroValueCart($event->getPage()->getCart())) {
            $this->removePayPalPaymentMethodFromConfirmPage($event);
        }
    }

    private function removePayPalPaymentMethodFromConfirmPage(CheckoutConfirmPageLoadedEvent $event): void
    {
        $paymentMethodCollection = $event->getPage()->getPaymentMethods();

        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($event->getContext());
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $paymentMethodCollection->remove($payPalPaymentMethodId);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
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
            AccountEditOrderPageLoadedEvent::class => ['onEditOrderPageLoaded', 1],
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $this->checkForMissingSettings($event);
        $this->checkForCartValue($event);
    }

    public function onEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $this->checkForMissingSettings($event);
        $this->checkForOrderValue($event);
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function checkForMissingSettings(PageLoadedEvent $event): void
    {
        try {
            $this->settingsValidationService->validate($event->getSalesChannelContext()->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            $this->logger->info('PayPal is removed from the available payment methods: {message}', ['message' => $e->getMessage()]);
            $this->removePayPalPaymentMethodFromConfirmPage($event);
        }
    }

    private function checkForCartValue(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($this->cartPriceService->isZeroValueCart($event->getPage()->getCart())) {
            $this->logger->info('PayPal is removed from the available payment methods, because the amount of the cart is zero');
            $this->removePayPalPaymentMethodFromConfirmPage($event);
        }
    }

    private function checkForOrderValue(AccountEditOrderPageLoadedEvent $event): void
    {
        $order = $event->getPage()->getOrder();

        if ($order->getPrice()->getTotalPrice() === 0.0) {
            $this->logger->info('PayPal is removed from the available payment methods, because the amount of the order is zero');
            $this->removePayPalPaymentMethodFromConfirmPage($event);
        }
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function removePayPalPaymentMethodFromConfirmPage(PageLoadedEvent $event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($event->getContext());
        if ($payPalPaymentMethodId === null) {
            return;
        }

        if ($event->getSalesChannelContext()->getPaymentMethod()->getId() === $payPalPaymentMethodId) {
            $paymentMethod = $paymentMethods->get($payPalPaymentMethodId);
            if ($paymentMethod !== null && $event instanceof CheckoutConfirmPageLoadedEvent) {
                $event->getPage()->getCart()->addErrors(new PaymentMethodBlockedError((string) $paymentMethod->getTranslation('name')));
            }
        }

        $paymentMethods->remove($payPalPaymentMethodId);
    }
}

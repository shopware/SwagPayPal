<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private PaymentMethodDataRegistry $methodDataRegistry;

    private LoggerInterface $logger;

    private CartPriceService $cartPriceService;

    public function __construct(
        PaymentMethodDataRegistry $methodDataRegistry,
        LoggerInterface $logger,
        CartPriceService $cartPriceService
    ) {
        $this->methodDataRegistry = $methodDataRegistry;
        $this->logger = $logger;
        $this->cartPriceService = $cartPriceService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
            AccountEditOrderPageLoadedEvent::class => ['onEditOrderPageLoaded', 1],
        ];
    }

    /**
     * @deprecated tag:v5.0.0 - will be removed, functionality has been moved to Swag\PayPal\Checkout\SalesChannel\FilteredPaymentMethodRoute
     */
    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->cartPriceService->isZeroValueCart($event->getPage()->getCart())) {
            return;
        }

        $this->removePayPalPaymentMethodsFromPage($event);
    }

    public function onEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $order = $event->getPage()->getOrder();

        if ($order->getPrice()->getTotalPrice() === 0.0) {
            $this->logger->info('PayPal is removed from the available payment methods, because the amount of the order is zero');
            $this->removePayPalPaymentMethodsFromPage($event);
        }
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function removePayPalPaymentMethodsFromPage(PageLoadedEvent $event): void
    {
        foreach ($event->getPage()->getPaymentMethods() as $paymentMethod) {
            if ($this->methodDataRegistry->isPayPalPaymentMethod($paymentMethod)) {
                $event->getPage()->getPaymentMethods()->remove($paymentMethod->getId());
            }
        }
    }
}

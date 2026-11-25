<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Log\Log;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CheckoutSubscriber implements EventSubscriberInterface
{
    private PaymentMethodDataRegistry $methodDataRegistry;

    private LoggerInterface $logger;

    public function __construct(
        PaymentMethodDataRegistry $methodDataRegistry,
        LoggerInterface $logger,
    ) {
        $this->methodDataRegistry = $methodDataRegistry;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => ['onEditOrderPageLoaded', 1],
        ];
    }

    public function onEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $order = $event->getPage()->getOrder();

        if ($order->getPrice()->getTotalPrice() === 0.0) {
            $this->logger->debug('Hid PayPal on the edit-order page, because the order amount is zero', Log::context(orderId: $order->getId()));
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

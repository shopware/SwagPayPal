<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBMarksDataServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated tag:v6.0.0 - will be removed without replacement, payment logos have been added natively
 */
class SPBMarksSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_MARKS_DATA_EXTENSION_ID = 'payPalSpbMarksData';

    private SPBMarksDataServiceInterface $spbMarksDataService;

    private LoggerInterface $logger;

    public function __construct(
        SPBMarksDataServiceInterface $spbMarksDataService,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->spbMarksDataService = $spbMarksDataService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //AccountEditOrderPageLoadedEvent::class => 'addMarksExtension',
            //AccountPaymentMethodPageLoadedEvent::class => 'addMarksExtension',
            //FooterPageletLoadedEvent::class => 'addMarksExtension',
            //CheckoutConfirmPageLoadedEvent::class => 'addMarksExtension',
        ];
    }

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|FooterPageletLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function addMarksExtension($event): void
    {
        $spbMarksData = $this->spbMarksDataService->getSpbMarksData($event->getSalesChannelContext());
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
}

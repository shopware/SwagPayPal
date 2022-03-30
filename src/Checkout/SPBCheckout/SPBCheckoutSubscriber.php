<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated tag:v6.0.0 - will be removed, has been split up into
 *      Swag\PayPal\Storefront\RequestSubscriber,
 *      Swag\PayPal\Storefront\Data\CheckoutDataSubscriber and
 *      Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData
 */
class SPBCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID = PayPalMethodData::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function onAccountOrderEditLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
    }

    public function addNecessaryRequestParameter(HandlePaymentMethodRouteRequestEvent $event): void
    {
        $this->logger->debug('Adding request parameter');
        $storefrontRequest = $event->getStorefrontRequest();
        $storeApiRequest = $event->getStoreApiRequest();

        $originalRoute = $storefrontRequest->attributes->get('_route');
        if ($originalRoute !== 'frontend.account.edit-order.update-order') {
            return;
        }

        $storeApiRequest->request->set(
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID,
            $storefrontRequest->request->get(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID)
        );
        $storeApiRequest->request->set(
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME,
            $storefrontRequest->request->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)
        );
        $this->logger->debug('Added request parameter');
    }
}

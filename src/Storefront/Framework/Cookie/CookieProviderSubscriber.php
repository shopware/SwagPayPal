<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Cookie;

use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CookieProviderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CookieGroupCollectEvent::class => 'onCookieGroupCollect',
        ];
    }

    public function onCookieGroupCollect(CookieGroupCollectEvent $event): void
    {
        $required = $event->cookieGroupCollection->get('cookie.groupRequired');
        if (!($entries = $required?->getEntries())) {
            return;
        }

        $payPalCookie = new CookieEntry('paypal-cookie-key');
        $payPalCookie->name = 'paypal.cookie.name';

        if ($this->paymentMethodUtil->isPaymentMethodActive($event->getSalesChannelContext(), [GooglePayHandler::class])) {
            $googleCookie = new CookieEntry('paypal-google-pay-cookie-key');
            $googleCookie->name = 'paypal.cookie.googlePay';

            $entries->add($payPalCookie);
            $entries->add($googleCookie);
        } elseif ($this->paymentMethodUtil->isPaymentMethodActive($event->getSalesChannelContext())) {
            $entries->add($payPalCookie);
        }
    }
}

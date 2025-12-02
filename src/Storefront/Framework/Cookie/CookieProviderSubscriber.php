<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Cookie;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CookieProviderSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     */
    public function __construct(
        private readonly EntityRepository $paymentMethodRepository,
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

        if ($this->isGooglePayActive($event->getSalesChannelContext())) {
            $googleCookie = new CookieEntry('paypal-google-pay-cookie-key');
            $googleCookie->name = 'paypal.cookie.googlePay';

            $entries->add($payPalCookie);
            $entries->add($googleCookie);
        } elseif ($this->isPayPalPaymentActive($event->getSalesChannelContext())) {
            $entries->add($payPalCookie);
        }
    }

    private function isPayPalPaymentActive(SalesChannelContext $salesChannelContext): bool
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new PrefixFilter('technicalName', 'swag_paypal_'));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId()));

        return $this->paymentMethodRepository->searchIds($criteria, $salesChannelContext->getContext())->firstId() !== null;
    }

    private function isGooglePayActive(SalesChannelContext $salesChannelContext): bool
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', GooglePayHandler::class));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannelId()));

        return $this->paymentMethodRepository->searchIds($criteria, $salesChannelContext->getContext())->firstId() !== null;
    }
}

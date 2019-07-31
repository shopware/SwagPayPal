<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\PayUponInvoice;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Swag\PayPal\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PayUponInvoiceSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    public function __construct(SettingsServiceInterface $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.payment_method.search.result.loaded' => ['onSearchResultLoaded', -1],
        ];
    }

    public function onSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        /** @var PaymentMethodCollection $paymentMethodCollection */
        $paymentMethodCollection = $event->getResult()->getEntities();

        if (!$this->collectionContainsPuiPaymentMethod($paymentMethodCollection)) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings(
                $event->getSalesChannelContext()->getSalesChannel()->getId()
            );
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($settings->getSpbCheckoutEnabled() && $settings->getSpbAlternativePaymentMethodsEnabled()) {
            return;
        }

        $paymentMethodCollection->filterAndReduceByProperty('handlerIdentifier', PayPalPuiPaymentHandler::class);
    }

    private function collectionContainsPuiPaymentMethod(PaymentMethodCollection $paymentMethodCollection): bool
    {
        return $paymentMethodCollection->filter(
            static function (PaymentMethodEntity $paymentMethod) {
                return $paymentMethod->getHandlerIdentifier() === PayPalPuiPaymentHandler::class;
            }
        )->count() !== 0;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PayUponInvoice;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated tag:v5.0.0 - will be removed
 */
class PayUponInvoiceSubscriber implements EventSubscriberInterface
{
    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //'sales_channel.payment_method.search.result.loaded' => ['onSearchResultLoaded', -1],
        ];
    }

    public function onSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        /** @var PaymentMethodCollection $paymentMethodCollection */
        $paymentMethodCollection = $event->getResult()->getEntities();

        if (!$this->collectionContainsPuiPaymentMethod($paymentMethodCollection)) {
            return;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        try {
            $this->settingsValidationService->validate($salesChannelId);
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if ($this->systemConfigService->getBool(Settings::SPB_CHECKOUT_ENABLED, $salesChannelId)
            && $this->systemConfigService->getBool(Settings::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED, $salesChannelId)) {
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

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\ACDC\Service\ACDCCheckoutDataServiceInterface;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ACDCCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID = 'payPalACDCFieldData';

    private SettingsValidationServiceInterface $settingsValidationService;

    private ACDCCheckoutDataServiceInterface $acdcCheckoutDataService;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        ACDCCheckoutDataServiceInterface $acdcCheckoutDataService,
        LoggerInterface $logger
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->acdcCheckoutDataService = $acdcCheckoutDataService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'onAccountOrderEditLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    public function onAccountOrderEditLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext())) {
            return;
        }

        $this->logger->debug('Adding data');
        $buttonData = $this->acdcCheckoutDataService->buildCheckoutData(
            $event->getSalesChannelContext(),
            $event->getPage()->getOrder()
        );

        $event->getPage()->addExtension(self::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID, $buttonData);
        $this->logger->debug('Added data');
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext())) {
            return;
        }

        if ($event->getPage()->getCart()->getErrors()->blockOrder()) {
            return;
        }

        $this->logger->debug('Adding data');
        $buttonData = $this->acdcCheckoutDataService->buildCheckoutData($event->getSalesChannelContext());

        $event->getPage()->addExtension(self::PAYPAL_ACDC_FIELD_DATA_EXTENSION_ID, $buttonData);
        $this->logger->debug('Added data');
    }

    private function checkSettings(SalesChannelContext $salesChannelContext): bool
    {
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== ACDCHandler::class) {
            return false;
        }

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        return true;
    }
}

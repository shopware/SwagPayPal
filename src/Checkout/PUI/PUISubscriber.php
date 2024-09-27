<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\Checkout\PUI\Service\PUIFraudNetDataService;
use Swag\PayPal\Checkout\PUI\Service\PUIPaymentInstructionDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PUISubscriber implements EventSubscriberInterface
{
    public const PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID = 'payPalPUIFraudNetPageData';
    public const PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID = 'payPalPUIFraudNetPageData';

    private SettingsValidationServiceInterface $settingsValidationService;

    private PUIFraudNetDataService $puiFraudNetDataService;

    private PUIPaymentInstructionDataService $puiPaymentInstructionDataService;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        PUIFraudNetDataService $puiFraudNetDataService,
        PUIPaymentInstructionDataService $puiPaymentInstructionDataService,
        LoggerInterface $logger,
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->puiFraudNetDataService = $puiFraudNetDataService;
        $this->puiPaymentInstructionDataService = $puiPaymentInstructionDataService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'onAccountOrderEditLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
        ];
    }

    public function onAccountOrderEditLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext())) {
            return;
        }

        $this->logger->debug('Adding data');
        $buttonData = $this->puiFraudNetDataService->buildCheckoutData($event->getSalesChannelContext());

        $event->getPage()->addExtension(self::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID, $buttonData);
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

        $puiFraudNetData = $this->puiFraudNetDataService->buildCheckoutData($event->getSalesChannelContext());
        $event->getPage()->addExtension(self::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID, $puiFraudNetData);
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext(), false)) {
            return;
        }

        $transactions = $event->getPage()->getOrder()->getTransactions();
        if (!$transactions || !($transaction = $transactions->last())) {
            return;
        }

        $this->logger->debug('Adding data');

        $puiPaymentInstructionData = $this->puiPaymentInstructionDataService->buildFinishData($transaction, $event->getSalesChannelContext());
        if (!$puiPaymentInstructionData) {
            return;
        }

        $event->getPage()->addExtension(self::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID, $puiPaymentInstructionData);
    }

    private function checkSettings(SalesChannelContext $salesChannelContext, bool $checkPaymentMethod = true): bool
    {
        if ($checkPaymentMethod && $salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== PUIHandler::class) {
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

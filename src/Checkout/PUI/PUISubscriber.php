<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI;

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

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        PUIFraudNetDataService $puiFraudNetDataService,
        PUIPaymentInstructionDataService $puiPaymentInstructionDataService,
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->puiFraudNetDataService = $puiFraudNetDataService;
        $this->puiPaymentInstructionDataService = $puiPaymentInstructionDataService;
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

        $buttonData = $this->puiFraudNetDataService->buildCheckoutData($event->getSalesChannelContext());

        $event->getPage()->addExtension(self::PAYPAL_PUI_FRAUDNET_PAGE_EXTENSION_ID, $buttonData);
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext())) {
            return;
        }

        if ($event->getPage()->getCart()->getErrors()->blockOrder()) {
            return;
        }

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

        $puiPaymentInstructionData = $this->puiPaymentInstructionDataService->buildFinishData($transaction, $event->getSalesChannelContext());
        if (!$puiPaymentInstructionData) {
            return;
        }

        $event->getPage()->addExtension(self::PAYPAL_PUI_PAYMENT_INSTRUCTIONS_PAGE_EXTENSION_ID, $puiPaymentInstructionData);

        if ($puiPaymentInstructionData->getPaymentInstructions() !== null) {
            return;
        }

        // @deprecated tag:v11.0.0 - remove early return with min-version of 6.7.2.0
        // @phpstan-ignore-next-line method may or may not exist depending on Shopware version
        if (!method_exists($event->getPage(), 'setLogoutCustomer')) {
            return;
        }

        // polling still needs the session, defer the logout to the reload
        $event->getPage()->setLogoutCustomer(false);
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

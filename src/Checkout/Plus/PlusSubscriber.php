<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 *
 * @internal
 */
#[Package('checkout')]
class PlusSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_PLUS_DATA_EXTENSION_ID = 'payPalPlusData';

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private PlusDataService $plusDataService;

    private PaymentMethodUtil $paymentMethodUtil;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        PlusDataService $plusDataService,
        PaymentMethodUtil $paymentMethodUtil,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->plusDataService = $plusDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
        ];
    }

    public function onAccountEditOrderLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->checkSettings($salesChannelContext, $event->getPage()->getPaymentMethods())) {
            return;
        }

        $this->logger->debug('Adding data');
        $page = $event->getPage();
        $plusData = $this->plusDataService->getPlusDataFromOrder($page->getOrder(), $salesChannelContext);
        $this->addPlusExtension($plusData, $page, $salesChannelContext);
        $this->logger->debug('Added data');
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $isExpressCheckout = $event->getRequest()->query->getBoolean(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID);
        if ($isExpressCheckout) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->checkSettings($salesChannelContext, $event->getPage()->getPaymentMethods())) {
            return;
        }

        $this->logger->debug('Adding data');
        $page = $event->getPage();
        $plusData = $this->plusDataService->getPlusData($page->getCart(), $salesChannelContext);
        $this->addPlusExtension($plusData, $page, $salesChannelContext);
        $this->logger->debug('Added data');
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $isPlus = $event->getRequest()->query->getBoolean(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID);
        if ($isPlus === false) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();

        $salesChannelId = $salesChannelContext->getSalesChannelId();

        try {
            $this->settingsValidationService->validate($salesChannelId);
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$this->systemConfigService->getBool(Settings::PLUS_CHECKOUT_ENABLED, $salesChannelId)
            || $this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_OTHER
        ) {
            return;
        }

        $transactions = $event->getPage()->getOrder()->getTransactions();
        if ($transactions === null) {
            return;
        }

        $payPalPaymentId = $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext());
        if ($payPalPaymentId === null) {
            return;
        }

        $transaction = $transactions->filterByPaymentMethodId($payPalPaymentId)->first();
        if ($transaction === null) {
            return;
        }

        $paymentMethod = $transaction->getPaymentMethod();
        if ($paymentMethod === null) {
            return;
        }

        $this->logger->debug('Changing payment method data');
        $this->changePaymentMethod($paymentMethod);
        $this->logger->debug('Changed payment method data');
    }

    private function checkSettings(SalesChannelContext $salesChannelContext, PaymentMethodCollection $paymentMethods): bool
    {
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext, $paymentMethods)) {
            return false;
        }

        $salesChannelId = $salesChannelContext->getSalesChannelId();

        try {
            $this->settingsValidationService->validate($salesChannelId);
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        if (!$this->systemConfigService->getBool(Settings::PLUS_CHECKOUT_ENABLED, $salesChannelId)
            || $this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_OTHER
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param AccountEditOrderPage|CheckoutConfirmPage $page
     */
    private function addPlusExtension(
        ?PlusData $plusData,
        Page $page,
        SalesChannelContext $salesChannelContext,
    ): void {
        if ($plusData === null) {
            return;
        }

        $payPalPaymentId = $plusData->getPaymentMethodId();
        $payPalPaymentMethodFromCollection = $page->getPaymentMethods()->get($payPalPaymentId);
        if ($payPalPaymentMethodFromCollection !== null) {
            $this->changePaymentMethod($payPalPaymentMethodFromCollection);
        }

        $currentSelectedPaymentMethod = $salesChannelContext->getPaymentMethod();
        if ($currentSelectedPaymentMethod->getId() !== $payPalPaymentId) {
            return;
        }

        $this->changePaymentMethod($currentSelectedPaymentMethod);

        $page->addExtension(self::PAYPAL_PLUS_DATA_EXTENSION_ID, $plusData);
    }

    private function changePaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $paymentMethod->addTranslated('name', $this->translator->trans('paypal.plus.paymentNameOverwrite'));

        $description = $paymentMethod->getTranslation('description');
        if ($description === null) {
            $description = $paymentMethod->getDescription();
        }

        $paymentMethod->addTranslated(
            'description',
            $description . ' ' . $this->translator->trans('paypal.plus.paymentDescriptionExtension')
        );
    }
}

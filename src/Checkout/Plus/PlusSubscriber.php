<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlusSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_PLUS_DATA_EXTENSION_ID = 'payPalPlusData';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PlusDataService
     */
    private $plusDataService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        SettingsServiceInterface $settingsService,
        PlusDataService $plusDataService,
        PaymentMethodUtil $paymentMethodUtil,
        TranslatorInterface $translator
    ) {
        $this->settingsService = $settingsService;
        $this->plusDataService = $plusDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->translator = $translator;
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
        $settings = $this->checkSettings($salesChannelContext, $event->getPage()->getPaymentMethods());
        if ($settings === null) {
            return;
        }

        $page = $event->getPage();
        $plusData = $this->plusDataService->getPlusDataFromOrder($page->getOrder(), $salesChannelContext, $settings);
        $this->addPlusExtension($plusData, $page, $salesChannelContext);
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $isExpressCheckout = $event->getRequest()->query->getBoolean(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID);
        if ($isExpressCheckout) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        $settings = $this->checkSettings($salesChannelContext, $event->getPage()->getPaymentMethods());
        if ($settings === null) {
            return;
        }

        $page = $event->getPage();
        $plusData = $this->plusDataService->getPlusData($page->getCart(), $salesChannelContext, $settings);
        $this->addPlusExtension($plusData, $page, $salesChannelContext);
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $isPlus = $event->getRequest()->query->getBoolean(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID);
        if ($isPlus === false) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPlusCheckoutEnabled()
            || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER
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

        $this->changePaymentMethod($paymentMethod);
    }

    private function checkSettings(SalesChannelContext $salesChannelContext, PaymentMethodCollection $paymentMethods): ?SwagPayPalSettingStruct
    {
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext, $paymentMethods)) {
            return null;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if (!$settings->getPlusCheckoutEnabled()
            || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER
        ) {
            return null;
        }

        return $settings;
    }

    /**
     * @param AccountEditOrderPage|CheckoutConfirmPage $page
     */
    private function addPlusExtension(
        ?PlusData $plusData,
        Page $page,
        SalesChannelContext $salesChannelContext
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
        $paymentMethod->addTranslated('name', $this->translator->trans('payPalPlus.paymentNameOverwrite'));

        $description = $paymentMethod->getTranslation('description');
        if ($description === null) {
            $description = $paymentMethod->getDescription();
        }

        $paymentMethod->addTranslated(
            'description',
            $description . ' ' . $this->translator->trans('payPalPlus.paymentDescriptionExtension')
        );
    }
}

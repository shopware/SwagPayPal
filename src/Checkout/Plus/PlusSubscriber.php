<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Swag\PayPal\Checkout\Plus\Service\PlusDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlusSubscriber implements EventSubscriberInterface
{
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

    public function __construct(
        SettingsServiceInterface $settingsService,
        PlusDataService $plusDataService,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->settingsService = $settingsService;
        $this->plusDataService = $plusDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
        ];
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        /*
         * PayPal Plus was disabled with PT-10610,
         * but we may need this business logic for the payment by invoice integration.
         */
        return;

        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->paymentMethodUtil->getPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPlusEnabled() || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_OTHER) {
            return;
        }

        $plusData = $this->plusDataService->getPlusData($event->getPage()->getCart(), $salesChannelContext, $settings);

        if ($plusData === null) {
            return;
        }

        $this->changePaymentMethod($event, $settings, $plusData, $salesChannelContext);

        $event->getPage()->addExtension('payPalPlusData', $plusData);
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        /*
         * PayPal Plus was disabled with PT-10610,
         * but we may need this business logic for the payment by invoice integration.
         */
        return;

        $salesChannelContext = $event->getSalesChannelContext();
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getPlusEnabled()) {
            return;
        }

        $overwritePaymentName = $settings->getPlusOverwritePaymentName();
        if ($overwritePaymentName === null || $overwritePaymentName === '') {
            return;
        }

        $currentSelectedPaymentMethod = $salesChannelContext->getPaymentMethod();
        $payPalPaymentId = $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext());
        $extendPaymentDescription = $settings->getPlusExtendPaymentDescription();
        if ($currentSelectedPaymentMethod->getId() === $payPalPaymentId) {
            $this->setNewValues($currentSelectedPaymentMethod, $overwritePaymentName, $extendPaymentDescription);
        }
    }

    private function changePaymentMethod(
        CheckoutConfirmPageLoadedEvent $event,
        SwagPayPalSettingStruct $settings,
        PlusData $plusData,
        SalesChannelContext $salesChannelContext
    ): void {
        $overwritePaymentName = $settings->getPlusOverwritePaymentName();
        if ($overwritePaymentName === null || $overwritePaymentName === '') {
            return;
        }

        $payPalPaymentId = $plusData->getPaymentMethodId();
        $payPalPaymentMethodFromCollection = $event->getPage()->getPaymentMethods()->get($payPalPaymentId);
        $extendPaymentDescription = $settings->getPlusExtendPaymentDescription();
        if ($payPalPaymentMethodFromCollection !== null) {
            $this->setNewValues($payPalPaymentMethodFromCollection, $overwritePaymentName, $extendPaymentDescription);
        }

        $currentSelectedPaymentMethod = $salesChannelContext->getPaymentMethod();
        if ($currentSelectedPaymentMethod->getId() === $payPalPaymentId) {
            $this->setNewValues($currentSelectedPaymentMethod, $overwritePaymentName, $extendPaymentDescription);
        }
    }

    private function setNewValues(
        PaymentMethodEntity $paymentMethod,
        string $overwritePaymentName,
        ?string $extendPaymentDescription
    ): void {
        $paymentMethod->addTranslated('name', $overwritePaymentName);

        if ($extendPaymentDescription !== null && $extendPaymentDescription !== '') {
            $description = $paymentMethod->getTranslation('description');
            $paymentMethod->addTranslated('description', $description . ' ' . $extendPaymentDescription);
        }
    }
}

<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SPBCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID = 'payPalSpbButtonData';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var SPBCheckoutDataService
     */
    private $spbCheckoutDataService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        SettingsServiceInterface $settingsService,
        SPBCheckoutDataService $spbCheckoutDataService,
        PaymentMethodUtil $paymentMethodUtil,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->settingsService = $settingsService;
        $this->spbCheckoutDataService = $spbCheckoutDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return;
        }

        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return;
        }

        if (!$settings->getSpbCheckoutEnabled()) {
            return;
        }

        $requestQuery = $event->getRequest()->query;
        if ($requestQuery->has(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME)
            && $requestQuery->has(EcsSpbHandler::PAYPAL_PAYMENT_ID_INPUT_NAME)
        ) {
            $this->flashBag->add('success', $this->translator->trans('smartPaymentButtons.confirmPageHint'));

            return;
        }

        $buttonData = $this->spbCheckoutDataService->getCheckoutData(
            $event->getSalesChannelContext(),
            $settings
        );

        $this->changePaymentMethodDescription($event->getPage()->getPaymentMethods(), $event->getContext());

        $event->getPage()->addExtension(self::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID, $buttonData);
    }

    private function changePaymentMethodDescription(PaymentMethodCollection $paymentMethods, Context $context): void
    {
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);
        if (!$payPalPaymentMethodId) {
            return;
        }

        $paypalPaymentMethod = $paymentMethods->get($payPalPaymentMethodId);
        if (!$paypalPaymentMethod) {
            return;
        }

        $paypalPaymentMethod->setTranslated([
            'description' => $this->translator->trans('smartPaymentButtons.description'),
        ]);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutController;
use Swag\PayPal\Checkout\Payment\Handler\AbstractPaymentHandler;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
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
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        SettingsServiceInterface $settingsService,
        SPBCheckoutDataService $spbCheckoutDataService,
        PaymentMethodUtil $paymentMethodUtil,
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->settingsService = $settingsService;
        $this->spbCheckoutDataService = $spbCheckoutDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->session = $session;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'onAccountOrderEditLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',

            HandlePaymentMethodRouteRequestEvent::class => 'addNecessaryRequestParameter',
        ];
    }

    public function onAccountOrderEditLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        $settings = $this->checkSettings($event->getSalesChannelContext(), $event->getPage()->getPaymentMethods());
        if ($settings === null) {
            return;
        }

        if ($this->addSuccessMessage($request)) {
            return;
        }

        $editOrderPage = $event->getPage();
        $buttonData = $this->spbCheckoutDataService->getCheckoutData(
            $event->getSalesChannelContext(),
            $settings,
            $editOrderPage->getOrder()->getId()
        );

        $currency = $editOrderPage->getOrder()->getCurrency();
        if ($currency === null) {
            $currency = $event->getSalesChannelContext()->getCurrency();
        }

        $buttonData->setDisabledAlternativePaymentMethods(
            $this->spbCheckoutDataService->getDisabledAlternativePaymentMethods(
                $editOrderPage->getOrder()->getAmountTotal(),
                $currency->getIsoCode()
            )
        );

        $this->changePaymentMethodDescription($editOrderPage->getPaymentMethods(), $event->getContext());

        $editOrderPage->addExtension(self::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID, $buttonData);
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        $settings = $this->checkSettings($event->getSalesChannelContext(), $event->getPage()->getPaymentMethods());
        if ($settings === null) {
            return;
        }

        $confirmPage = $event->getPage();
        if ($confirmPage->getCart()->getExtension(ExpressCheckoutController::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID) !== null) {
            return;
        }

        if ($this->addSuccessMessage($request)) {
            return;
        }

        $buttonData = $this->spbCheckoutDataService->getCheckoutData(
            $event->getSalesChannelContext(),
            $settings
        );

        $buttonData->setDisabledAlternativePaymentMethods(
            $this->spbCheckoutDataService->getDisabledAlternativePaymentMethods(
                $confirmPage->getCart()->getPrice()->getTotalPrice(),
                $event->getSalesChannelContext()->getCurrency()->getIsoCode()
            )
        );

        $this->changePaymentMethodDescription($confirmPage->getPaymentMethods(), $event->getContext());

        $confirmPage->addExtension(self::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID, $buttonData);
    }

    public function addNecessaryRequestParameter(HandlePaymentMethodRouteRequestEvent $event): void
    {
        $storefrontRequest = $event->getStorefrontRequest();
        $storeApiRequest = $event->getStoreApiRequest();

        $originalRoute = $storefrontRequest->attributes->get('_route');
        if ($originalRoute !== 'frontend.account.edit-order.update-order') {
            return;
        }

        $storeApiRequest->request->set(
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID,
            $storefrontRequest->request->get(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID)
        );
        $storeApiRequest->request->set(
            AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME,
            $storefrontRequest->request->get(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME)
        );
        $storeApiRequest->request->set(
            EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME,
            $storefrontRequest->request->get(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME)
        );
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

        if (!$settings->getSpbCheckoutEnabled()
            || $settings->getMerchantLocation() === SwagPayPalSettingStruct::MERCHANT_LOCATION_GERMANY
        ) {
            return null;
        }

        return $settings;
    }

    private function addSuccessMessage(Request $request): bool
    {
        $requestQuery = $request->query;
        if ($requestQuery->has(EcsSpbHandler::PAYPAL_PAYER_ID_INPUT_NAME)
            && $requestQuery->has(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME)
        ) {
            $this->session->getFlashBag()->add('success', $this->translator->trans('paypal.smartPaymentButtons.confirmPageHint'));

            return true;
        }

        return false;
    }

    private function changePaymentMethodDescription(PaymentMethodCollection $paymentMethods, Context $context): void
    {
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $paypalPaymentMethod = $paymentMethods->get($payPalPaymentMethodId);
        if ($paypalPaymentMethod === null) {
            return;
        }

        $paypalPaymentMethod->addTranslated('description', $this->translator->trans('paypal.smartPaymentButtons.description'));
    }
}

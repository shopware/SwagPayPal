<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\Payment\Handler\AbstractPaymentHandler;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataServiceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class SPBCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID = 'payPalSpbButtonData';

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private SPBCheckoutDataServiceInterface $spbCheckoutDataService;

    private PaymentMethodUtil $paymentMethodUtil;

    private Session $session;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        SPBCheckoutDataServiceInterface $spbCheckoutDataService,
        PaymentMethodUtil $paymentMethodUtil,
        Session $session,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->spbCheckoutDataService = $spbCheckoutDataService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->session = $session;
        $this->translator = $translator;
        $this->logger = $logger;
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
        if (!$this->checkSettings($event->getSalesChannelContext(), $event->getPage()->getPaymentMethods())) {
            return;
        }

        if ($this->addSuccessMessage($request)) {
            $this->logger->debug('Added success message');

            return;
        }

        $this->logger->debug('Adding data');
        $editOrderPage = $event->getPage();
        $buttonData = $this->spbCheckoutDataService->buildCheckoutData(
            $event->getSalesChannelContext(),
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
        $this->logger->debug('Added data');
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->checkSettings($event->getSalesChannelContext(), $event->getPage()->getPaymentMethods())) {
            return;
        }

        $confirmPage = $event->getPage();
        if ($confirmPage->getCart()->getExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID) !== null) {
            return;
        }

        if ($confirmPage->getCart()->getErrors()->blockOrder()) {
            return;
        }

        if ($this->addSuccessMessage($request)) {
            $this->logger->debug('Added success message');

            return;
        }

        $this->logger->debug('Adding data');
        $buttonData = $this->spbCheckoutDataService->buildCheckoutData($event->getSalesChannelContext());

        $buttonData->setDisabledAlternativePaymentMethods(
            $this->spbCheckoutDataService->getDisabledAlternativePaymentMethods(
                $confirmPage->getCart()->getPrice()->getTotalPrice(),
                $event->getSalesChannelContext()->getCurrency()->getIsoCode()
            )
        );

        $this->changePaymentMethodDescription($confirmPage->getPaymentMethods(), $event->getContext());

        $confirmPage->addExtension(self::PAYPAL_SMART_PAYMENT_BUTTONS_DATA_EXTENSION_ID, $buttonData);
        $this->logger->debug('Added data');
    }

    public function addNecessaryRequestParameter(HandlePaymentMethodRouteRequestEvent $event): void
    {
        $this->logger->debug('Adding request parameter');
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
            AbstractPaymentHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME,
            $storefrontRequest->request->get(AbstractPaymentHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)
        );
        $this->logger->debug('Added request parameter');
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

        if (!$this->systemConfigService->getBool(Settings::SPB_CHECKOUT_ENABLED, $salesChannelId)
            || $this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_GERMANY
        ) {
            return false;
        }

        return true;
    }

    private function addSuccessMessage(Request $request): bool
    {
        $requestQuery = $request->query;
        if ($requestQuery->has(EcsSpbHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)) {
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

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCheckoutDataServiceInterface;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID = 'payPalEcsButtonData';

    private ExpressCheckoutDataServiceInterface $expressCheckoutDataService;

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private PaymentMethodUtil $paymentMethodUtil;

    private LoggerInterface $logger;

    public function __construct(
        ExpressCheckoutDataServiceInterface $service,
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        PaymentMethodUtil $paymentMethodUtil,
        LoggerInterface $logger
    ) {
        $this->expressCheckoutDataService = $service;
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            SearchPageLoadedEvent::class => 'addExpressCheckoutDataToPage',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
            GuestWishlistPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',

            SwitchBuyBoxVariantEvent::class => 'addExpressCheckoutDataToBuyBoxSwitch',

            'framework.validation.address.create' => 'disableAddressValidation',
            'framework.validation.customer.create' => 'disableCustomerValidation',

            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    public function addExpressCheckoutDataToPage(PageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $eventName = \get_class($event);

        $addProductToCart = $event instanceof ProductPageLoadedEvent
            || $event instanceof NavigationPageLoadedEvent
            || $event instanceof SearchPageLoadedEvent;

        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($salesChannelContext, $eventName, $addProductToCart);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $event->getPage()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
        $this->logger->debug('Added data to page {page}', ['page' => \get_class($event)]);
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed. Use \Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCategoryRoute instead
     */
    public function addExpressCheckoutDataToCmsPage(CmsPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($salesChannelContext, \get_class($event), true);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        /** @var CmsPageCollection $pages */
        $pages = $event->getResult();

        $cmsPage = $pages->first();

        if ($cmsPage === null) {
            return;
        }

        $cmsPage->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    public function addExpressCheckoutDataToPagelet(PageletLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($salesChannelContext, \get_class($event), true);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $event->getPagelet()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    public function addExpressCheckoutDataToBuyBoxSwitch(SwitchBuyBoxVariantEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($salesChannelContext, \get_class($event), true);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $event->getProduct()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    public function disableAddressValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressPrepareCheckoutRoute::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('additionalAddressLine1')
                               ->set('additionalAddressLine2')
                               ->set('phoneNumber');
    }

    public function disableCustomerValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressPrepareCheckoutRoute::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('birthdayDay')
                               ->set('birthdayMonth')
                               ->set('birthdayYear');
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($event->getRequest()->query->has(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID) === false) {
            return;
        }

        $confirmPage = $event->getPage();
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($event->getContext());
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $paymentMethods = $confirmPage->getPaymentMethods();
        if ($paymentMethods->has($payPalPaymentMethodId) === false) {
            return;
        }

        $filtered = $paymentMethods->filterByProperty('id', $payPalPaymentMethodId);
        $confirmPage->setPaymentMethods($filtered);
        $this->logger->debug('Removed other payment methods from selection for Express Checkout');
    }

    private function getExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        string $eventName,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $settings = $this->checkSettings($salesChannelContext, $eventName);
        if ($settings === false) {
            return null;
        }

        return $this->expressCheckoutDataService->buildExpressCheckoutButtonData(
            $salesChannelContext,
            $addProductToCart
        );
    }

    private function checkSettings(SalesChannelContext $context, string $eventName): bool
    {
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($context) === false) {
            return false;
        }

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        if ($this->expressOptionForEventEnabled($context->getSalesChannelId(), $eventName) === false) {
            return false;
        }

        return true;
    }

    private function expressOptionForEventEnabled(string $salesChannelId, string $eventName): bool
    {
        switch ($eventName) {
            case ProductPageLoadedEvent::class:
            case QuickviewPageletLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_DETAIL_ENABLED, $salesChannelId);
            case OffcanvasCartPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_OFF_CANVAS_ENABLED, $salesChannelId);
            case CheckoutRegisterPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_LOGIN_ENABLED, $salesChannelId);
            case CheckoutCartPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_CART_ENABLED, $salesChannelId);
            case NavigationPageLoadedEvent::class:
            case CmsPageLoadedEvent::class:
            case SearchPageLoadedEvent::class:
            case GuestWishlistPageletLoadedEvent::class:
            case SwitchBuyBoxVariantEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_LISTING_ENABLED, $salesChannelId);
            default:
                return false;
        }
    }
}

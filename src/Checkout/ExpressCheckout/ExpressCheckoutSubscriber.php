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
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
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
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID = 'payPalEcsButtonData';

    /**
     * @var PayPalExpressCheckoutDataService
     */
    private $expressCheckoutDataService;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PayPalExpressCheckoutDataService $service,
        SettingsServiceInterface $settingsService,
        PaymentMethodUtil $paymentMethodUtil,
        LoggerInterface $logger
    ) {
        $this->expressCheckoutDataService = $service;
        $this->settingsService = $settingsService;
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

            CmsPageLoadedEvent::class => 'addExpressCheckoutDataToCmsPage',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
            GuestWishlistPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',

            'framework.validation.address.create' => 'disableAddressValidation',
            'framework.validation.customer.create' => 'disableCustomerValidation',
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

    private function getExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        string $eventName,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $settings = $this->checkSettings($salesChannelContext, $eventName);
        if ($settings === null) {
            return null;
        }

        return $this->expressCheckoutDataService->getExpressCheckoutButtonData(
            $salesChannelContext,
            $settings,
            $addProductToCart
        );
    }

    private function checkSettings(SalesChannelContext $context, string $eventName): ?SwagPayPalSettingStruct
    {
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($context) === false) {
            return null;
        }

        try {
            $settings = $this->settingsService->getSettings($context->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if ($this->expressOptionForEventEnabled($settings, $eventName) === false) {
            return null;
        }

        return $settings;
    }

    private function expressOptionForEventEnabled(SwagPayPalSettingStruct $settings, string $eventName): bool
    {
        switch ($eventName) {
            case ProductPageLoadedEvent::class:
            case QuickviewPageletLoadedEvent::class:
                return $settings->getEcsDetailEnabled();
            case OffcanvasCartPageLoadedEvent::class:
                return $settings->getEcsOffCanvasEnabled();
            case CheckoutRegisterPageLoadedEvent::class:
                return $settings->getEcsLoginEnabled();
            case CheckoutCartPageLoadedEvent::class:
                return $settings->getEcsCartEnabled();
            case NavigationPageLoadedEvent::class:
            case CmsPageLoadedEvent::class:
            case SearchPageLoadedEvent::class:
            case GuestWishlistPageletLoadedEvent::class:
                return $settings->getEcsListingEnabled();
            default:
                return false;
        }
    }
}

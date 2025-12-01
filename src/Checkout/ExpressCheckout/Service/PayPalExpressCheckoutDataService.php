<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\AbstractScriptDataService;
use Swag\PayPal\Storefront\Data\Struct\AbstractScriptData;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\Availability\AvailabilityContextBuilder;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\Lifecycle\Method\VenmoMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class PayPalExpressCheckoutDataService extends AbstractScriptDataService
{
    private const ADD_TO_CART_EVENTS = [
        PageletLoadedEvent::class,
        ProductPageLoadedEvent::class,
        NavigationPageLoadedEvent::class,
        SearchPageLoadedEvent::class,
    ];

    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        LocaleCodeProvider $localeCodeProvider,
        private readonly RouterInterface $router,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        TokenResource $tokenResource,
        private readonly CartPriceService $cartPriceService,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
        private readonly AvailabilityContextBuilder $availabilityContextBuilder,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil, $tokenResource);
    }

    /**
     * @deprecated tag:v11.0.0 - Parameter $addProductToCart will be removed, use $event parameter instead
     */
    public function buildExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        bool $addProductToCart = false,
        ?ShopwareSalesChannelEvent $event = null
    ): ?ExpressCheckoutButtonData {
        if (!$this->isAvailable($event, $salesChannelContext)) {
            return null;
        }

        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $availabilityContext = $this->getAvailabilityContext($event, $salesChannelContext);
        $fundingSources = $this->getFundingSources($availabilityContext);

        if (!$fundingSources) {
            return null;
        }

        return (new ExpressCheckoutButtonData())->assign([
            ...parent::getBaseData($salesChannelContext),
            'productDetailEnabled' => $this->systemConfigService->getBool(Settings::ECS_DETAIL_ENABLED, $salesChannelId),
            'offCanvasEnabled' => $this->systemConfigService->getBool(Settings::ECS_OFF_CANVAS_ENABLED, $salesChannelId),
            'loginEnabled' => $this->systemConfigService->getBool(Settings::ECS_LOGIN_ENABLED, $salesChannelId),
            'cartEnabled' => $this->systemConfigService->getBool(Settings::ECS_CART_ENABLED, $salesChannelId),
            'listingEnabled' => $this->systemConfigService->getBool(Settings::ECS_LISTING_ENABLED, $salesChannelId),
            'buttonColor' => $this->systemConfigService->getString(Settings::ECS_BUTTON_COLOR, $salesChannelId),
            'buttonShape' => $this->systemConfigService->getString(Settings::ECS_BUTTON_SHAPE, $salesChannelId),
            'addProductToCart' => $addProductToCart,
            'contextSwitchUrl' => $this->router->generate('frontend.paypal.express.prepare_cart'),
            'payPalPaymentMethodId' => $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            'createOrderUrl' => $this->router->generate('frontend.paypal.express.create_order'),
            'prepareCheckoutUrl' => $this->router->generate('frontend.paypal.express.prepare_checkout'),
            'checkoutConfirmUrl' => $this->router->generate(
                'frontend.checkout.confirm.page',
                [PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true],
                RouterInterface::ABSOLUTE_URL
            ),
            'handleErrorUrl' => $this->router->generate('frontend.paypal.handle-error'),
            'cancelRedirectUrl' => $this->router->generate($addProductToCart ? 'frontend.checkout.cart.page' : 'frontend.checkout.register.page'),
            'showPayLater' => \in_array('paylater', $fundingSources, true),
            'fundingSources' => $fundingSources,
            'pageType' => $this->getPageType($event),
        ]);
    }

    protected function getButtonLanguageSetting(): string
    {
        return Settings::ECS_BUTTON_LANGUAGE_ISO;
    }

    protected function isAvailable(ShopwareSalesChannelEvent $event, SalesChannelContext $salesChannelContext): bool
    {
        if (!$this->isAddToCartEvent($event)) {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

            if ($cart->getLineItems()->count() === 0) {
                return false;
            }

            if ($this->cartPriceService->isZeroValueCart($cart)) {
                return false;
            }
        }

        if ($salesChannelContext->getCustomer()?->getActive()) {
            return false;
        }

        return true;
    }

    protected function getAvailabilityContext(ShopwareSalesChannelEvent $event, SalesChannelContext $salesChannelContext): AvailabilityContext
    {
        if ($event instanceof ProductPageLoadedEvent) {
            return $this->availabilityContextBuilder->buildFromProduct(
                $event->getPage()->getProduct(),
                $salesChannelContext
            );
        }

        if ($this->isAddToCartEvent($event)) {
            return $this->availabilityContextBuilder->buildFromSalesChannelContext($salesChannelContext);
        }

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $this->availabilityContextBuilder->buildFromCart($cart, $salesChannelContext);
    }

    protected function isAddToCartEvent(ShopwareSalesChannelEvent $event): bool
    {
        foreach (self::ADD_TO_CART_EVENTS as $addToCartEvent) {
            if ($event instanceof $addToCartEvent) {
                return true;
            }
        }

        return false;
    }

    private function getPageType(ShopwareSalesChannelEvent $event): ?string
    {
        return match (true) {
            $event instanceof ProductPageLoadedEvent,
            $event instanceof QuickviewPageletLoadedEvent => AbstractScriptData::PAGE_TYPE_PRODUCT_DETAILS,
            $event instanceof OffcanvasCartPageLoadedEvent => AbstractScriptData::PAGE_TYPE_MINI_CART,
            $event instanceof CheckoutRegisterPageLoadedEvent => AbstractScriptData::PAGE_TYPE_CHECKOUT,
            $event instanceof CheckoutCartPageLoadedEvent => AbstractScriptData::PAGE_TYPE_CART,
            $event instanceof NavigationPageLoadedEvent,
            $event instanceof CmsPageLoadedEvent,
            $event instanceof SearchPageLoadedEvent,
            $event instanceof GuestWishlistPageletLoadedEvent,
            $event instanceof SwitchBuyBoxVariantEvent,
            $event instanceof SalesChannelEntitySearchResultLoadedEvent => AbstractScriptData::PAGE_TYPE_PRODUCT_LISTING,
            default => null,
        };
    }

    private function getFundingSources(AvailabilityContext $context): array
    {
        $fundingSources = [];

        if ($this->paymentMethodDataRegistry->getPaymentMethod(PayPalMethodData::class)->isAvailable($context)) {
            $fundingSources[] = 'paypal';
        }

        if ($this->systemConfigService->getBool(Settings::ECS_SHOW_PAY_LATER, $context->getSalesChannelId()) && $this->paymentMethodDataRegistry->getPaymentMethod(PayLaterMethodData::class)->isAvailable($context)) {
            $fundingSources[] = 'paylater';
        }

        if ($this->paymentMethodDataRegistry->getPaymentMethod(VenmoMethodData::class)->isAvailable($context)) {
            $fundingSources[] = 'venmo';
        }

        return $fundingSources;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

class PayPalExpressCheckoutDataService implements ExpressCheckoutDataServiceInterface
{
    private CartService $cartService;

    private LocaleCodeProvider $localeCodeProvider;

    private RouterInterface $router;

    private PaymentMethodUtil $paymentMethodUtil;

    private SystemConfigService $systemConfigService;

    public function __construct(
        CartService $cartService,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        PaymentMethodUtil $paymentMethodUtil,
        SystemConfigService $systemConfigService
    ) {
        $this->cartService = $cartService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @deprecated tag:v4.0.0 - will be removed, use buildExpressCheckoutButtonData instead
     */
    public function getExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        return $this->buildExpressCheckoutButtonData($salesChannelContext, $addProductToCart);
    }

    public function buildExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        if (!$addProductToCart && $cart->getLineItems()->count() === 0) {
            return null;
        }

        $customer = $salesChannelContext->getCustomer();
        if ($customer instanceof CustomerEntity && $customer->getActive()) {
            return null;
        }

        $context = $salesChannelContext->getContext();
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $clientId = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId)
            ? $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId)
            : $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);

        return (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => $this->systemConfigService->getBool(Settings::ECS_DETAIL_ENABLED, $salesChannelId),
            'offCanvasEnabled' => $this->systemConfigService->getBool(Settings::ECS_OFF_CANVAS_ENABLED, $salesChannelId),
            'loginEnabled' => $this->systemConfigService->getBool(Settings::ECS_LOGIN_ENABLED, $salesChannelId),
            'cartEnabled' => $this->systemConfigService->getBool(Settings::ECS_CART_ENABLED, $salesChannelId),
            'listingEnabled' => $this->systemConfigService->getBool(Settings::ECS_LISTING_ENABLED, $salesChannelId),
            'buttonColor' => $this->systemConfigService->getString(Settings::ECS_BUTTON_COLOR, $salesChannelId),
            'buttonShape' => $this->systemConfigService->getString(Settings::ECS_BUTTON_SHAPE, $salesChannelId),
            'clientId' => $clientId,
            'languageIso' => $this->getInContextButtonLanguage($salesChannelId, $context),
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'addProductToCart' => $addProductToCart,
            'contextSwitchUrl' => $this->generateRoute('store-api.switch-context'),
            'payPaLPaymentMethodId' => $this->paymentMethodUtil->getPayPalPaymentMethodId($context),
            'createOrderUrl' => $this->generateRoute('store-api.paypal.express.create_order'),
            'deleteCartUrl' => $this->generateRoute('store-api.checkout.cart.delete'),
            'prepareCheckoutUrl' => $this->generateRoute('store-api.paypal.express.prepare_checkout'),
            'checkoutConfirmUrl' => $this->router->generate(
                'frontend.checkout.confirm.page',
                [PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true],
                RouterInterface::ABSOLUTE_URL
            ),
            'addErrorUrl' => $this->router->generate('payment.paypal.add_error'),
        ]);
    }

    private function getInContextButtonLanguage(string $salesChannelId, Context $context): string
    {
        if ($settingsLocale = $this->systemConfigService->getString(Settings::ECS_BUTTON_LANGUAGE_ISO, $salesChannelId)) {
            return $settingsLocale;
        }

        return \str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context)
        );
    }

    private function generateRoute(string $routeName): string
    {
        return $this->router->generate($routeName);
    }
}

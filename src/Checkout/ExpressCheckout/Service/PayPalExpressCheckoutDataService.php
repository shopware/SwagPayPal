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
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StoreApiProxyController;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

class PayPalExpressCheckoutDataService
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var LocaleCodeProvider
     */
    private $localeCodeProvider;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(
        CartService $cartService,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->cartService = $cartService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public function getExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings,
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

        return (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => $settings->getEcsDetailEnabled(),
            'offCanvasEnabled' => $settings->getEcsOffCanvasEnabled(),
            'loginEnabled' => $settings->getEcsLoginEnabled(),
            'cartEnabled' => $settings->getEcsCartEnabled(),
            'listingEnabled' => $settings->getEcsListingEnabled(),
            'buttonColor' => $settings->getEcsButtonColor(),
            'buttonShape' => $settings->getEcsButtonShape(),
            'clientId' => $settings->getSandbox() ? $settings->getClientIdSandbox() : $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($settings, $context),
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($settings->getIntent()),
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
            'useStoreApi' => \class_exists(StoreApiProxyController::class),
            'approvePaymentUrl' => $this->router->generate('payment.paypal.approve_payment'),
        ]);
    }

    private function getInContextButtonLanguage(SwagPayPalSettingStruct $settings, Context $context): string
    {
        if ($settingsLocale = $settings->getEcsButtonLanguageIso()) {
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
        return $this->router->generate($routeName, ['version' => PlatformRequest::API_VERSION]);
    }
}

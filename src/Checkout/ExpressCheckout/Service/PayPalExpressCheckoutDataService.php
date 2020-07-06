<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
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

    public function __construct(
        CartService $cartService,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router
    ) {
        $this->cartService = $cartService;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
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

        return (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => $settings->getEcsDetailEnabled(),
            'offCanvasEnabled' => $settings->getEcsOffCanvasEnabled(),
            'loginEnabled' => $settings->getEcsLoginEnabled(),
            'cartEnabled' => $settings->getEcsCartEnabled(),
            'listingEnabled' => $settings->getEcsListingEnabled(),
            'buttonColor' => $settings->getEcsButtonColor(),
            'buttonShape' => $settings->getEcsButtonShape(),
            'clientId' => $settings->getSandbox() ? $settings->getClientIdSandbox() : $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($settings, $salesChannelContext),
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
            'addProductToCart' => $addProductToCart,
            'createPaymentUrl' => $this->router->generate('sales-channel-api.action.paypal.create_payment', ['version' => PlatformRequest::API_VERSION]),
            'createNewCartUrl' => $this->router->generate('sales-channel-api.action.paypal.create_new_cart', ['version' => PlatformRequest::API_VERSION]),
            'addLineItemUrl' => $this->router->generate('frontend.checkout.line-item.add'),
            'approvePaymentUrl' => $this->router->generate('payment.paypal.approve_payment'),
            'checkoutConfirmUrl' => $this->router->generate(
                'frontend.checkout.confirm.page',
                [PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true],
                RouterInterface::ABSOLUTE_URL
            ),
            'addErrorUrl' => $this->router->generate('payment.paypal.add_error'),
        ]);
    }

    private function getInContextButtonLanguage(SwagPayPalSettingStruct $settings, SalesChannelContext $context): string
    {
        if ($settingsLocale = $settings->getEcsButtonLanguageIso()) {
            return $settingsLocale;
        }

        return \str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}

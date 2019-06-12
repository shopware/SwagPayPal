<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Util\LocaleCodeProvider;

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

    public function __construct(
        CartService $cartService,
        LocaleCodeProvider $localeCodeProvider
    ) {
        $this->cartService = $cartService;
        $this->localeCodeProvider = $localeCodeProvider;
    }

    public function getExpressCheckoutButtonData(
        SalesChannelContext $context,
        SwagPayPalSettingGeneralStruct $settings,
        ?bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $customer = $context->getCustomer();

        if ((!$cart instanceof Cart || $cart->getLineItems()->count() === 0) && !$addProductToCart) {
            return null;
        }

        if ($customer instanceof CustomerEntity && $customer->getActive()) {
            return null;
        }

        $buttonData = (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => $settings->getEcsDetailEnabled(),
            'offCanvasEnabled' => $settings->getEcsOffCanvasEnabled(),
            'loginEnabled' => $settings->getEcsLoginEnabled(),
            'cartEnabled' => $settings->getEcsCartEnabled(),
            'listingEnabled' => $settings->getEcsListingEnabled(),
            'useSandbox' => $settings->getSandbox(),
            'buttonColor' => $settings->getEcsButtonColor(),
            'buttonShape' => $settings->getEcsButtonShape(),
            'clientId' => $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($settings, $context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
            'addProductToCart' => $addProductToCart,
        ]);

        return $buttonData;
    }

    private function getInContextButtonLanguage(
        SwagPayPalSettingGeneralStruct $settings,
        SalesChannelContext $context): ?string
    {
        if ($settingsLocale = $settings->getEcsButtonLanguageIso()) {
            return $settingsLocale;
        }

        return str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}

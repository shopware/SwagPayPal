<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
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

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function getExpressCheckoutButtonData(
        SalesChannelContext $context,
        SwagPayPalSettingStruct $settings,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        if (!$addProductToCart && (!$cart instanceof Cart || $cart->getLineItems()->count() === 0)) {
            return null;
        }

        $customer = $context->getCustomer();
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

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getInContextButtonLanguage(SwagPayPalSettingStruct $settings, SalesChannelContext $context): string
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

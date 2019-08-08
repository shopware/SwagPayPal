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

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function getExpressCheckoutButtonData(
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings,
        bool $addProductToCart = false
    ): ?ExpressCheckoutButtonData {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        if (!$addProductToCart && (!$cart instanceof Cart || $cart->getLineItems()->count() === 0)) {
            return null;
        }

        $customer = $salesChannelContext->getCustomer();
        if ($customer instanceof CustomerEntity && $customer->getActive()) {
            return null;
        }

        $buttonData = (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => $settings->getEcsDetailEnabled(),
            'offCanvasEnabled' => $settings->getEcsOffCanvasEnabled(),
            'loginEnabled' => $settings->getEcsLoginEnabled(),
            'cartEnabled' => $settings->getEcsCartEnabled(),
            'listingEnabled' => $settings->getEcsListingEnabled(),
            'buttonColor' => $settings->getEcsButtonColor(),
            'buttonShape' => $settings->getEcsButtonShape(),
            'clientId' => $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($settings, $salesChannelContext),
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
            'addProductToCart' => $addProductToCart,
            'createPaymentUrl' => $this->router->generate('sales-channel-api.action.paypal.create_payment', ['version' => 1]),
            'createNewCartUrl' => $this->router->generate('sales-channel-api.action.paypal.create_new_cart', ['version' => 1]),
            'addLineItemUrl' => $this->router->generate('frontend.checkout.line-item.add'),
            'approvePaymentUrl' => $this->router->generate('paypal.approve_payment'),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
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

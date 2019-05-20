<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;

class PayPalExpressCheckoutDataService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepo;

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var CartService
     */
    private $cartService;

    public function __construct(
        EntityRepositoryInterface $languageRepo,
        EntityRepositoryInterface $currencyRepo,
        SettingsService $settingsService,
        CartService $cartService
    ) {
        $this->languageRepo = $languageRepo;
        $this->currencyRepo = $currencyRepo;
        $this->settingsService = $settingsService;
        $this->cartService = $cartService;
    }

    public function getExpressCheckoutButtonData(SalesChannelContext $context): ?ExpressCheckoutButtonData
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $customer = $context->getCustomer();

        try {
            $settings = $this->settingsService->getSettings($context->getSalesChannel()->getId());
        } catch (PayPalSettingsNotFoundException $e) {
            return null;
        }

        if (!$cart instanceof Cart || $cart->getLineItems()->count() === 0) {
            return null;
        }

        if ($customer instanceof CustomerEntity && $customer->getActive()) {
            return null;
        }

        $buttonData = (new ExpressCheckoutButtonData())->assign([
            'offCanvasEnabled' => $settings->getEcsOffCanvasEnabled(),
            'loginEnabled' => $settings->getEcsLoginEnabled(),
            'cartEnabled' => $settings->getEcsCartEnabled(),
            'useSandbox' => $settings->getSandbox(),
            'buttonColor' => $settings->getEcsButtonColor(),
            'buttonShape' => $settings->getEcsButtonShape(),
            'clientId' => $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($settings, $context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
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

        $iso = $context->getSalesChannel()->getLanguage()->getLocale()->getCode();

        return str_replace('-', '_', $iso);
    }
}

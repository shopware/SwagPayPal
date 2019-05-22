<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\PaymentMethodIdProvider;

class SPBCheckoutDataService
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var PaymentMethodIdProvider
     */
    private $paymentMethodIdProvider;

    public function __construct(SettingsServiceInterface $settingsService, PaymentMethodIdProvider $paymentMethodIdProvider)
    {
        $this->settingsService = $settingsService;
        $this->paymentMethodIdProvider = $paymentMethodIdProvider;
    }

    public function getCheckoutData(CheckoutConfirmPage $checkoutConfirmPage): ?SPBCheckoutButtonData
    {
        $context = $checkoutConfirmPage->getContext();
        try {
            $settings = $this->settingsService->getSettings($context->getSalesChannel()->getId());
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if (!$settings->isSpbCheckoutEnabled()) {
            return null;
        }

        $cart = $checkoutConfirmPage->getCart();
        if ($cart->getLineItems()->count() === 0) {
            return null;
        }

        if (!$context->getCustomer() instanceof CustomerEntity || !$context->getCustomer()->getActive()) {
            return null;
        }
        $checkoutConfirmPage->getPaymentMethods();

        $paymentMethodId = $this->paymentMethodIdProvider->getPayPalPaymentMethodId($context->getContext());
        $expressCheckoutData = (new SPBCheckoutButtonData())->assign([
            'enabled' => $settings->isSpbCheckoutEnabled(),
            'useSandbox' => $settings->getSandbox(),
            'clientId' => $settings->getClientId(),
            'languageIso' => $this->getInContextButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
            'paymentMethodId' => $paymentMethodId,
        ]);

        return $expressCheckoutData;
    }

    private function getInContextButtonLanguage(SalesChannelContext $context): ?string
    {
        $iso = $context->getSalesChannel()->getLanguage()->getLocale()->getCode();

        return str_replace('-', '_', $iso);
    }
}

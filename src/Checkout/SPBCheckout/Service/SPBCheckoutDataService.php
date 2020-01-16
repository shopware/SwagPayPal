<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

class SPBCheckoutDataService
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var LocaleCodeProvider
     */
    private $localeCodeProvider;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
    }

    public function getCheckoutData(
        SalesChannelContext $context,
        SwagPayPalSettingStruct $settings
    ): SPBCheckoutButtonData {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context->getContext());

        return (new SPBCheckoutButtonData())->assign([
            'clientId' => $settings->getClientId(),
            'languageIso' => $this->getButtonLanguage($settings, $context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => $settings->getIntent(),
            'paymentMethodId' => $paymentMethodId,
            'useAlternativePaymentMethods' => $settings->getSpbAlternativePaymentMethodsEnabled(),
            'createPaymentUrl' => $this->router->generate('sales-channel-api.action.paypal.spb.create_payment', ['version' => 1]),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
            'buttonShape' => $settings->getSpbButtonShape(),
            'buttonColor' => $settings->getSpbButtonColor(),
        ]);
    }

    private function getButtonLanguage(SwagPayPalSettingStruct $settings, SalesChannelContext $context): string
    {
        if ($settingsLocale = $settings->getSpbButtonLanguageIso()) {
            return $settingsLocale;
        }

        return str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}

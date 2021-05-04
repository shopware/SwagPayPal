<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

class SPBCheckoutDataService
{
    private const APM_BLIK = 'blik';
    private const APM_EPS = 'eps';
    private const APM_GIROPAY = 'giropay';
    private const APM_P24 = 'p24';
    private const APM_SOFORT = 'sofort';

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
        SwagPayPalSettingStruct $settings,
        ?string $orderId = null
    ): SPBCheckoutButtonData {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context->getContext());

        $spbCheckoutButtonData = (new SPBCheckoutButtonData())->assign([
            'clientId' => $settings->getSandbox() ? $settings->getClientIdSandbox() : $settings->getClientId(),
            'languageIso' => $this->getButtonLanguage($settings, $context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($settings->getIntent()),
            'buttonShape' => $settings->getSpbButtonShape(),
            'buttonColor' => $settings->getSpbButtonColor(),
            'paymentMethodId' => $paymentMethodId,
            'useAlternativePaymentMethods' => $settings->getSpbAlternativePaymentMethodsEnabled(),
            'createOrderUrl' => $this->router->generate('store-api.paypal.spb.create_order', ['version' => PlatformRequest::API_VERSION]),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
            'addErrorUrl' => $this->router->generate('payment.paypal.add_error'),
        ]);

        if ($orderId !== null) {
            $spbCheckoutButtonData->setOrderId($orderId);
            $spbCheckoutButtonData->setAccountOrderEditUrl(
                $this->router->generate(
                    'frontend.account.edit-order.page',
                    ['orderId' => $orderId],
                    RouterInterface::ABSOLUTE_URL
                )
            );
        }

        return $spbCheckoutButtonData;
    }

    /**
     * @return string[]
     */
    public function getDisabledAlternativePaymentMethods(float $totalPrice, string $currencyIsoCode): array
    {
        $disabled = [];

        if ($totalPrice < 1.0 && $currencyIsoCode === 'EUR') {
            $disabled[] = self::APM_EPS;
            $disabled[] = self::APM_GIROPAY;
            $disabled[] = self::APM_SOFORT;
        }

        if ($totalPrice < 1.0 && $currencyIsoCode === 'PLN') {
            $disabled[] = self::APM_BLIK;
        }

        if (($totalPrice < 1.0 || $totalPrice > 55000.0) && $currencyIsoCode === 'PLN') {
            $disabled[] = self::APM_P24;
        }

        return $disabled;
    }

    private function getButtonLanguage(SwagPayPalSettingStruct $settings, SalesChannelContext $context): string
    {
        if ($settingsLocale = $settings->getSpbButtonLanguageIso()) {
            return $settingsLocale;
        }

        return \str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}

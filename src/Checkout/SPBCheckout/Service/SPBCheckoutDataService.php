<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\Routing\RouterInterface;

class SPBCheckoutDataService implements SPBCheckoutDataServiceInterface
{
    private const APM_BLIK = 'blik';
    private const APM_EPS = 'eps';
    private const APM_GIROPAY = 'giropay';
    private const APM_P24 = 'p24';
    private const APM_SOFORT = 'sofort';

    private PaymentMethodUtil $paymentMethodUtil;

    private LocaleCodeProvider $localeCodeProvider;

    private RouterInterface $router;

    private SystemConfigService $systemConfigService;

    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    public function buildCheckoutData(
        SalesChannelContext $context,
        ?string $orderId = null
    ): SPBCheckoutButtonData {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context->getContext());
        $salesChannelId = $context->getSalesChannelId();
        $clientId = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId)
            ? $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId)
            : $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);

        $spbCheckoutButtonData = (new SPBCheckoutButtonData())->assign([
            'clientId' => $clientId,
            'languageIso' => $this->getButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'buttonShape' => $this->systemConfigService->getString(Settings::SPB_BUTTON_SHAPE, $salesChannelId),
            'buttonColor' => $this->systemConfigService->getString(Settings::SPB_BUTTON_COLOR, $salesChannelId),
            'paymentMethodId' => $paymentMethodId,
            'useAlternativePaymentMethods' => $this->systemConfigService->getBool(Settings::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED, $salesChannelId),
            'showPayLater' => $this->systemConfigService->getBool(Settings::SPB_SHOW_PAY_LATER, $salesChannelId),
            'createOrderUrl' => $this->router->generate('store-api.paypal.create_order'),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
            'addErrorUrl' => $this->router->generate('store-api.paypal.error'),
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

    private function getButtonLanguage(SalesChannelContext $context): string
    {
        if ($settingsLocale = $this->systemConfigService->getString(Settings::SPB_BUTTON_LANGUAGE_ISO, $context->getSalesChannelId())) {
            return $settingsLocale;
        }

        return \str_replace(
            '-',
            '_',
            $this->localeCodeProvider->getLocaleCodeFromContext($context->getContext())
        );
    }
}

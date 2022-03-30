<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksData;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;

class SPBMarksDataService implements SPBMarksDataServiceInterface
{
    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private CredentialsUtilInterface $credentialsUtil;

    private PaymentMethodUtil $paymentMethodUtil;

    private LocaleCodeProvider $localeCodeProvider;

    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        PaymentMethodUtil $paymentMethodUtil,
        LocaleCodeProvider $localeCodeProvider
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->credentialsUtil = $credentialsUtil;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->localeCodeProvider = $localeCodeProvider;
    }

    public function getSpbMarksData(SalesChannelContext $salesChannelContext): ?SPBMarksData
    {
        if (!$this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext)) {
            return null;
        }

        $salesChannelId = $salesChannelContext->getSalesChannelId();

        try {
            $this->settingsValidationService->validate($salesChannelId);
        } catch (PayPalSettingsInvalidException $e) {
            return null;
        }

        if (!$this->systemConfigService->getBool(Settings::SPB_CHECKOUT_ENABLED, $salesChannelId)
            || $this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_GERMANY
        ) {
            return null;
        }

        $data = new SPBMarksData();
        $data->assign([
            'clientId' => $this->credentialsUtil->getClientId($salesChannelId),
            'merchantPayerId' => $this->credentialsUtil->getMerchantPayerId($salesChannelId),
            'paymentMethodId' => (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            'useAlternativePaymentMethods' => $this->systemConfigService->getBool(Settings::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED, $salesChannelId),
            'showPayLater' => $this->systemConfigService->getBool(Settings::SPB_SHOW_PAY_LATER, $salesChannelId),
            'languageIso' => $this->getButtonLanguage($salesChannelContext),
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
        ]);

        return $data;
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

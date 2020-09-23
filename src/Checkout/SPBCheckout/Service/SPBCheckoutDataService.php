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
            'intent' => $settings->getIntent(),
            'buttonShape' => $settings->getSpbButtonShape(),
            'buttonColor' => $settings->getSpbButtonColor(),
            'paymentMethodId' => $paymentMethodId,
            'useAlternativePaymentMethods' => $settings->getSpbAlternativePaymentMethodsEnabled(),
            'createPaymentUrl' => $this->router->generate('sales-channel-api.action.paypal.spb.create_payment', ['version' => PlatformRequest::API_VERSION]),
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

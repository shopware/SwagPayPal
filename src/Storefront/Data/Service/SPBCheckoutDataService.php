<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class SPBCheckoutDataService extends AbstractCheckoutDataService
{
    private const APM_BLIK = 'blik';
    private const APM_EPS = 'eps';
    private const APM_P24 = 'p24';

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        private readonly VaultDataService $vaultDataService,
    ) {
        parent::__construct($paymentMethodDataRegistry, $localeCodeProvider, $router, $systemConfigService, $credentialsUtil);
    }

    public function buildCheckoutData(
        SalesChannelContext $context,
        ?Cart $cart = null,
        ?OrderEntity $order = null,
    ): ?SPBCheckoutButtonData {
        $salesChannelId = $context->getSalesChannelId();
        $currency = $order?->getCurrency() ?? $context->getCurrency();

        if ($cart && $cart->getExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID) !== null) {
            return null;
        }

        if ($this->systemConfigService->getString(Settings::MERCHANT_LOCATION, $salesChannelId) === Settings::MERCHANT_LOCATION_GERMANY
            || !$this->systemConfigService->getBool(Settings::SPB_CHECKOUT_ENABLED, $salesChannelId)
        ) {
            return null;
        }

        if ($cart !== null) {
            $price = $cart->getPrice()->getTotalPrice();
        } elseif ($order !== null) {
            $price = $order->getAmountTotal();
        } else {
            $price = 0.0;
        }

        $data = $this->getBaseData($context, $order);

        return (new SPBCheckoutButtonData())->assign(\array_merge($data, [
            'buttonColor' => $this->systemConfigService->getString(Settings::SPB_BUTTON_COLOR, $salesChannelId),
            'useAlternativePaymentMethods' => $this->systemConfigService->getBool(Settings::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED, $salesChannelId),
            'disabledAlternativePaymentMethods' => $this->getDisabledAlternativePaymentMethods($price, $currency->getIsoCode()),
            'showPayLater' => $this->systemConfigService->getBool(Settings::SPB_SHOW_PAY_LATER, $salesChannelId),
            'userIdToken' => $this->vaultDataService->getUserIdToken($context),
        ]));
    }

    public function getMethodDataClass(): string
    {
        return PayPalMethodData::class;
    }

    /**
     * @return string[]
     */
    private function getDisabledAlternativePaymentMethods(float $totalPrice, string $currencyIsoCode): array
    {
        $disabled = [];

        if ($totalPrice < 1.0 && $currencyIsoCode === 'EUR') {
            $disabled[] = self::APM_EPS;
        }

        if ($totalPrice < 1.0 && $currencyIsoCode === 'PLN') {
            $disabled[] = self::APM_BLIK;
        }

        if (($totalPrice < 1.0 || $totalPrice > 55000.0) && $currencyIsoCode === 'PLN') {
            $disabled[] = self::APM_P24;
        }

        return $disabled;
    }
}

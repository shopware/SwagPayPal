<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Struct\AbstractCheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
abstract class AbstractCheckoutDataService
{
    public const PAYPAL_ERROR = 'isPayPalError';

    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
        private readonly LocaleCodeProvider $localeCodeProvider,
        private readonly RouterInterface $router,
        protected readonly SystemConfigService $systemConfigService,
        private readonly CredentialsUtilInterface $credentialsUtil
    ) {
    }

    abstract public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?AbstractCheckoutData;

    /**
     * @return class-string<AbstractMethodData>
     */
    abstract public function getMethodDataClass(): string;

    protected function getBaseData(SalesChannelContext $context, ?OrderEntity $order = null): array
    {
        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod($this->getMethodDataClass()),
            $context->getContext()
        );

        $salesChannelId = $context->getSalesChannelId();
        $customer = $context->getCustomer();

        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $data = [
            'clientId' => $this->credentialsUtil->getClientId($salesChannelId),
            'merchantPayerId' => $this->credentialsUtil->getMerchantPayerId($salesChannelId),
            'languageIso' => $this->getButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'buttonShape' => $this->systemConfigService->getString(Settings::SPB_BUTTON_SHAPE, $salesChannelId),
            'paymentMethodId' => $paymentMethodId,
            'createOrderUrl' => $context->hasExtension('subscription')
                ? $this->router->generate('frontend.subscription.paypal.create_order', ['subscriptionToken' => $context->getToken()])
                : $this->router->generate('frontend.paypal.create_order'),
            'addErrorUrl' => $this->router->generate('frontend.paypal.error'),
        ];

        if ($order !== null) {
            $data['orderId'] = $order->getId();
            $data['accountOrderEditFailedUrl'] = $this->router->generate(
                'frontend.account.edit-order.page',
                [
                    'orderId' => $order->getId(),
                    'error-code' => (new AsyncPaymentProcessException($order->getId(), ''))->getErrorCode(),
                    self::PAYPAL_ERROR => 1,
                ],
                RouterInterface::ABSOLUTE_URL
            );
            $data['accountOrderEditCancelledUrl'] = $this->router->generate(
                'frontend.account.edit-order.page',
                [
                    'orderId' => $order->getId(),
                    'error-code' => (new CustomerCanceledAsyncPaymentException($order->getId()))->getErrorCode(),
                ],
                RouterInterface::ABSOLUTE_URL
            );
        }

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

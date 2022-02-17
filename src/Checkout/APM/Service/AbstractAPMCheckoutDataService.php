<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\APM\Service;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\APM\APMCheckoutData;
use Swag\PayPal\RestApi\V1\Resource\IdentityResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractAPMCheckoutDataService
{
    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private IdentityResource $identityResource;

    private LocaleCodeProvider $localeCodeProvider;

    private RouterInterface $router;

    private SystemConfigService $systemConfigService;

    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        IdentityResource $identityResource,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService
    ) {
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->identityResource = $identityResource;
        $this->localeCodeProvider = $localeCodeProvider;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    abstract public function buildCheckoutData(SalesChannelContext $context, ?OrderEntity $order = null): APMCheckoutData;

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
        $clientId = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId)
            ? $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId)
            : $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId);
        $customer = $context->getCustomer();

        if ($customer === null) {
            throw new CustomerNotLoggedInException();
        }

        $data = [
            'clientId' => $clientId,
            'clientToken' => $this->identityResource->getClientToken($salesChannelId)->getClientToken(),
            'languageIso' => $this->getButtonLanguage($context),
            'currency' => $context->getCurrency()->getIsoCode(),
            'intent' => \mb_strtolower($this->systemConfigService->getString(Settings::INTENT, $salesChannelId)),
            'buttonShape' => $this->systemConfigService->getString(Settings::SPB_BUTTON_SHAPE, $salesChannelId),
            'paymentMethodId' => $paymentMethodId,
            'createOrderUrl' => $this->router->generate('store-api.paypal.create_order'),
            'checkoutConfirmUrl' => $this->router->generate('frontend.checkout.confirm.page', [], RouterInterface::ABSOLUTE_URL),
            'addErrorUrl' => $this->router->generate('store-api.paypal.error'),
        ];

        if ($order !== null) {
            $data['orderId'] = $order->getId();
            $data['accountOrderEditUrl'] = $this->router->generate(
                'frontend.account.edit-order.page',
                ['orderId' => $order->getId()],
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

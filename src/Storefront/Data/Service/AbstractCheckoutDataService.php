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
use Shopware\Core\Checkout\Payment\PaymentException;
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
abstract class AbstractCheckoutDataService extends AbstractScriptDataService
{
    public const PAYPAL_ERROR = 'isPayPalError';

    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
        LocaleCodeProvider $localeCodeProvider,
        private readonly RouterInterface $router,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil);
    }

    abstract public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?AbstractCheckoutData;

    /**
     * @return class-string<AbstractMethodData>
     */
    abstract public function getMethodDataClass(): string;

    protected function getBaseData(SalesChannelContext $context, ?OrderEntity $order = null): array
    {
        if ($context->getCustomer() === null) {
            throw CartException::customerNotLoggedIn();
        }

        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod($this->getMethodDataClass()),
            $context->getContext()
        );

        $salesChannelId = $context->getSalesChannelId();

        $data = [
            ...parent::getBaseData($context, $order),
            'buttonShape' => $this->systemConfigService->getString(Settings::SPB_BUTTON_SHAPE, $salesChannelId),
            'paymentMethodId' => $paymentMethodId,
            'createOrderUrl' => $context->hasExtension('subscription')
                ? $this->router->generate('frontend.subscription.paypal.create_order', ['subscriptionToken' => $context->getToken()])
                : $this->router->generate('frontend.paypal.create_order'),
            /** @deprecated tag:v10.0.0 - Will be removed, use handleErrorUrl instead */
            'addErrorUrl' => $this->router->generate('frontend.paypal.error'),
            'handleErrorUrl' => $this->router->generate('frontend.paypal.handle-error'),
            'brandName' => $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelId)
                ?: ($context->getSalesChannel()->getTranslation('name') ?? ''),
        ];

        if ($order !== null) {
            $data['orderId'] = $order->getId();
            /** @deprecated tag:v10.0.0 - Will be removed, use handleErrorUrl instead */
            $data['accountOrderEditFailedUrl'] = $this->router->generate(
                'frontend.account.edit-order.page',
                [
                    'orderId' => $order->getId(),
                    'error-code' => PaymentException::PAYMENT_ASYNC_PROCESS_INTERRUPTED,
                    self::PAYPAL_ERROR => 1,
                ],
                RouterInterface::ABSOLUTE_URL
            );
            /** @deprecated tag:v10.0.0 - Will be removed, use handleErrorUrl instead */
            $data['accountOrderEditCancelledUrl'] = $this->router->generate(
                'frontend.account.edit-order.page',
                [
                    'orderId' => $order->getId(),
                    'error-code' => PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL,
                ],
                RouterInterface::ABSOLUTE_URL
            );
        }

        return $data;
    }
}

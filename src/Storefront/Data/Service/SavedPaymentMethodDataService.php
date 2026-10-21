<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Commercial\Subscription\Framework\Struct\PlanIntervalMappingStruct;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Struct\SavedPaymentMethodData;
use Swag\PayPal\Storefront\Service\SavedPaymentMethodEditService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

/**
 * Builds the data for changing the funding instrument of a saved PayPal wallet with the JS SDK v6.
 * A buyer with a saved wallet pays with the regular order button instead of the PayPal button.
 */
#[Package('checkout')]
class SavedPaymentMethodDataService extends AbstractCheckoutDataService
{
    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        LocaleCodeProvider $localeCodeProvider,
        RouterInterface $router,
        SystemConfigService $systemConfigService,
        CredentialsUtilInterface $credentialsUtil,
        private readonly SavedPaymentMethodEditService $savedPaymentMethodEditService,
    ) {
        parent::__construct($paymentMethodDataRegistry, $localeCodeProvider, $router, $systemConfigService, $credentialsUtil);
    }

    /**
     * Expects a buyer who already saved their PayPal wallet
     */
    public function buildCheckoutData(
        SalesChannelContext $context,
        ?Cart $cart = null,
        ?OrderEntity $order = null,
    ): ?SavedPaymentMethodData {
        if ($context->getPaymentMethod()->getHandlerIdentifier() !== $this->methodData->getHandler()) {
            return null;
        }

        if (!$this->systemConfigService->getBool(Settings::SDK_V6_ENABLED, $context->getSalesChannelId())) {
            return null;
        }

        if ($cart?->getExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID) !== null) {
            return null;
        }

        // subscriptions request vaulting with every order, which PayPal's edit flow is not specified for
        if ($this->isSubscription($context, $order)) {
            return null;
        }

        return (new SavedPaymentMethodData())->assign([
            ...$this->getBaseData($context, $order),
            'editUrl' => $this->router->generate('frontend.paypal.saved_payment_method.edit'),
            'approvedOrderId' => $this->savedPaymentMethodEditService->getApprovedOrderId($context, $order?->getId()),
        ]);
    }

    public function getMethodDataClass(): string
    {
        return PayPalMethodData::class;
    }

    private function isSubscription(SalesChannelContext $context, ?OrderEntity $order): bool
    {
        if ($context->hasExtension('subscription')) {
            return true;
        }

        if (\class_exists(PlanIntervalMappingStruct::class) && $context->getExtensionOfType('subscriptionManagedContexts', PlanIntervalMappingStruct::class)?->all()) {
            return true;
        }

        $initialSubscriptions = $order?->getExtension('initialSubscriptions');

        return $initialSubscriptions instanceof \Countable && \count($initialSubscriptions) > 0;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\PaymentResumeService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PayPalPaymentHandler extends AbstractPaymentMethodHandler
{
    public const PAYPAL_EXPRESS_CHECKOUT_ID = 'isPayPalExpressCheckout';

    /**
     * @internal
     */
    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        StateMachineRegistry $stateMachineRegistry,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        OrderResource $orderResource,
        VaultTokenService $vaultTokenService,
        EntityRepository $orderTransactionRepository,
        AbstractOrderBuilder $orderBuilder,
        private readonly PaymentResumeService $paymentResumeService,
    ) {
        parent::__construct($settingsValidationService, $stateMachineRegistry, $orderExecuteService, $orderPatchService, $transactionDataService, $orderResource, $vaultTokenService, $orderTransactionRepository, $orderBuilder);
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): ?RedirectResponse {
        try {
            return parent::pay($request, $transaction, $context, $validateStruct);
        } catch (PayerActionRequiredException $e) {
            return $this->recoverFromPayerAction($e, $request, $transaction);
        }
    }

    protected function resolvePartnerAttributionId(Request $request): string
    {
        if ($request->request->getBoolean(self::PAYPAL_EXPRESS_CHECKOUT_ID)) {
            return PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT;
        }

        if ($request->request->getAlnum(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME)) {
            return PartnerAttributionId::SMART_PAYMENT_BUTTONS;
        }

        return PartnerAttributionId::PAYPAL_PPCP;
    }

    protected function isVaultable(): bool
    {
        return true;
    }

    protected function requirePreparedOrder(): bool
    {
        return false;
    }

    protected function resolveRedirect(?Order $order): ?string
    {
        if ($order === null || !$order->isset('links')) {
            return null;
        }

        return parent::resolveRedirect($order) ?? $order->getLinks()->getRelation(Link::RELATION_APPROVE)?->getHref();
    }

    // must not confirm the payment source again, which would void the renewed consent
    private function recoverFromPayerAction(
        PayerActionRequiredException $exception,
        Request $request,
        PaymentTransactionStruct $transaction,
    ): RedirectResponse {
        $preparedOrderId = $request->request->getAlnum(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $action = $exception->getPayerActionUrl();
        $returnUrl = $transaction->getReturnUrl();
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        // only storefront-created orders lead back into the shop {@see PayPalController::addRestoreUrls}
        if ($preparedOrderId === ''
            || $action === null
            || $returnUrl === null
            || !$request->hasSession(true)
            || !$this->isStorefrontRequest($request)
            || !$salesChannelContext instanceof SalesChannelContext
        ) {
            throw $exception;
        }

        // the payer returns in this session, so remembering it there binds the resume to them
        $this->paymentResumeService->store(
            $request->getSession(),
            $preparedOrderId,
            $returnUrl,
            $salesChannelContext->getSalesChannelId(),
        );

        return new RedirectResponse($action);
    }

    private function isStorefrontRequest(Request $request): bool
    {
        // shopware/storefront is an optional dependency and may not be installed
        return \class_exists(StorefrontRouteScope::class)
            && \in_array(StorefrontRouteScope::ID, $request->attributes->get(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, []), true);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgentCommerce\Validation\CartTokenValidator;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['paypal-agent'], '_agentScope' => [AgentSource::SCOPE_CART, AgentSource::SCOPE_CHECKOUT]])]
class CheckoutRoute extends AbstractAgentCommerceRoute
{
    public function __construct(
        private readonly AbstractCartOrderRoute $orderRoute,
        private readonly CartService $cartService,
        private readonly OrderResource $orderResource,
        private readonly PayPalPaymentHandler $paymentHandler,
        private readonly PayPalCartTransformer $cartTransformer,
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart/{token}/checkout', name: 'api.paypal.merchant-cart.checkout', methods: [Request::METHOD_POST])]
    public function checkout(string $token, Request $request, SalesChannelContext $context): AgentCartResponse
    {
        $extractedToken = CartTokenValidator::validateCartToken($token);

        $cart = $this->cartService->getCart($extractedToken, $context);
        if (!$cart->getLineItems()->count()) {
            // We don't create a cart with empty items. So it must be created.
            throw AgentException::cartNotFound($token);
        }

        $order = $this->orderRoute
            ->order($cart, $context, new RequestDataBag($request->request->all()))
            ->getOrder();

        $primaryTransactionId = $order->getTransactions()?->last()?->getId();

        // @deprecated tag:v11.0.0 - remove if condition with min-version of 6.7.1.0, keep content
        if (\method_exists($order, 'getPrimaryOrderTransactionId')) {
            $primaryTransactionId = $order->getPrimaryOrderTransactionId();
        }

        if (!$primaryTransactionId) {
            throw AgentException::orderSystemError();
        }

        $body = \json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $payPalOrder = $this->orderResource->get($body['payment_method']['token'], $context->getSalesChannelId());

        $request->request->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, $payPalOrder->getId());

        $payPalCart = $this->cartTransformer->convertToPayPalCart($cart, $context);
        $payPalCart->setStatus(PayPalCart::STATUS__COMPLETE);
        $payPalCart->setPaymentMethod($this->createPaymentMethod($payPalOrder->getId()));

        $response = $this->paymentHandler->pay($request, new PaymentTransactionStruct($primaryTransactionId), $context->getContext(), null);

        if ($response instanceof RedirectResponse) {
            $payPalCart->setStatus(PayPalCart::STATUS__INCOMPLETE);
            $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__INVALID);
            $payPalCart->getPaymentMethod()?->setApprovalUrl($response->getTargetUrl());
        }

        return new AgentCartResponse($payPalCart);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Struct\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgentCommerce\Validation\CartTokenValidator;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
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
        $cart = $this->cartService->getCart(CartTokenValidator::validateCartToken($token), $context);
        if (!$cart->getLineItems()->count()) {
            // We don't create a cart with empty items. So it must be created.
            throw AgentException::cartNotFound($token);
        }

        $body = \json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $payPalOrder = $this->orderResource->get($body['payment_method']['token'], $context->getSalesChannelId());
        if ($payPalOrder->getStatus() !== PaymentStatusV2::ORDER_APPROVED) {
            return new AgentCartResponse($this->handleNotApprovedOrder($payPalOrder));
        }

        $payPalCart = $this->cartTransformer->convertToPayPalCart($cart, $context);
        $payPalCart->setPaymentMethod($this->createPaymentMethod($payPalOrder->getId()));

        try {
            $this->handleOrder($request, $payPalOrder, $cart, $context);
            $payPalCart->setStatus(PayPalCart::STATUS__COMPLETE);
        } catch (PaymentException) {
            $payPalCart->setStatus(PayPalCart::STATUS__INCOMPLETE);
            $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__INVALID);
        }

        return new AgentCartResponse($payPalCart);
    }

    private function handleNotApprovedOrder(Order $payPalOrder): PayPalCart
    {
        $payPalCart = new PayPalCart();
        $payPalCart->setStatus(PayPalCart::STATUS__INCOMPLETE);
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__INVALID);
        $payPalCart->setPaymentMethod($this->createPaymentMethod($payPalOrder->getId()));

        $link = $payPalOrder->getLinks()->getRelation(Link::RELATION_APPROVE)
            ?? $payPalOrder->getLinks()->getRelation(Link::RELATION_PAYER_ACTION);
        $payPalCart->getPaymentMethod()?->setApprovalUrl($link?->getHref());

        return $payPalCart;
    }

    private function handleOrder(Request $request, Order $payPalOrder, Cart $cart, SalesChannelContext $context): void
    {
        $order = $this->orderRoute
            ->order($cart, $context, new RequestDataBag($request->request->all()))
            ->getOrder();

        $primaryTransaction = $order->getTransactions()?->last();
        if (!$primaryTransaction) {
            throw AgentException::orderSystemError();
        }

        $request->request->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, $payPalOrder->getId());
        $request->query->set(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN, $payPalOrder->getId());

        // @phpstan-ignore new.deprecated
        $payment = new AsyncPaymentTransactionStruct($primaryTransaction, $order, '');

        $this->paymentHandler->pay($payment, new RequestDataBag($request->request->all()), $context);
        $this->paymentHandler->finalize($payment, $request, $context);
    }
}

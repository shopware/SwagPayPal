<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\AgenticCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgenticCommerce\Validation\CartTokenValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => [AgentRouteScope::ID], '_agentScope' => [AgentSource::SCOPE_CART]])]
class GetCartRoute
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PayPalCartTransformer $payPalCartTransformer,
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart/{token}', name: 'api.paypal.merchant-cart.get', methods: [Request::METHOD_GET])]
    public function getCart(string $token, SalesChannelContext $salesChannelContext): AgentCartResponse
    {
        $extractedToken = CartTokenValidator::validateCartToken($token);

        $cart = $this->cartService->getCart($extractedToken, $salesChannelContext);
        if (!$cart->getLineItems()->count()) {
            // We don't create a cart with empty items. So it must be created.
            throw AgentException::cartNotFound($token);
        }

        $payPalCart = $this->payPalCartTransformer->convertToPayPalCart($cart, $salesChannelContext);
        $payPalCart->setStatus($payPalCart->getValidationStatus() === PayPalCart::VALIDATION_STATUS__VALID ? PayPalCart::STATUS__READY : PayPalCart::STATUS__INCOMPLETE);

        return new AgentCartResponse($payPalCart);
    }
}

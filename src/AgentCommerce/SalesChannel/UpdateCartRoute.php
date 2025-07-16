<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['paypal-agent'], '_agentScope' => [AgentSource::SCOPE_CHECKOUT]])]
class UpdateCartRoute
{
    #[Route('/api/paypal/v1/merchant-cart/{token}', name: 'api.paypal.merchant-cart.update', methods: [Request::METHOD_PUT])]
    public function updateCart(string $token, Request $request, Context $context): AgentCartResponse
    {
        return new AgentCartResponse($token);
    }
}

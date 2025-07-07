<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Swag\PayPal\AgentCommerce\Exception\PayPalAgentException;
use Swag\PayPal\AgentCommerce\Routing\PayPalAgentSource;
use Swag\PayPal\AgentCommerce\Routing\PayPalAgentSourceGuard;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\PayPalAgentCartResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['paypal-agent']])]
class UpdateCartRoute extends AbstractUpdateCartRoute
{
    public function getDecorated(): AbstractUpdateCartRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route('/api/paypal/v1/merchant-cart/{token}', name: 'api.paypal.merchant-cart.update', methods: [Request::METHOD_PUT])]
    public function updateCart(string $token, Request $request, Context $context): PayPalAgentCartResponse
    {
        if (!PayPalAgentSourceGuard::validForScopes([PayPalAgentSource::SCOPE_CART], $context->getSource())) {
            throw PayPalAgentException::unauthorized('Unauthorized JWT token');
        }

        return new PayPalAgentCartResponse($token);
    }
}

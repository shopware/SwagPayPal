<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ClientTokenRoute extends AbstractClientTokenRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly TokenResource $tokenResource,
    ) {
    }

    public function getDecorated(): AbstractClientTokenRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/paypal/client-token',
        operationId: 'paypalClientToken',
        description: 'Retrieves a client ID token for the current sales channel',
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Client ID token',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'token',
                type: 'string'
            )])
        )]
    )]
    #[Route(path: '/store-api/paypal/client-token', name: 'store-api.paypal.client-token', methods: ['POST'], defaults: ['_loginRequired' => false])]
    public function getClientToken(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $clientToken = $this->tokenResource->getClientToken($salesChannelContext)->getAccessToken();

        return new TokenResponse($clientToken);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ClientTokenRoute extends AbstractClientTokenRoute
{
    public const REQUEST_VAULT = 'vault';

    /**
     * @internal
     */
    public function __construct(
        private readonly TokenResource $tokenResource,
        private readonly VaultTokenService $vaultTokenService,
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
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(
                property: self::REQUEST_VAULT,
                description: 'Binds the token to the logged-in customer\'s saved account or card for the selected payment method, so the PayPal JS SDK can show it, e.g. to let the customer change the funding instrument of their saved PayPal wallet',
                type: 'boolean',
                default: false,
            ),
        ])),
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
        if (!$request->request->getBoolean(self::REQUEST_VAULT)) {
            return new TokenResponse($this->tokenResource->getClientToken($salesChannelContext)->getAccessToken());
        }

        $vaultId = $this->getVaultId($salesChannelContext);

        return new TokenResponse($this->tokenResource->getVaultClientToken($salesChannelContext, $vaultId)->getAccessToken());
    }

    private function getVaultId(SalesChannelContext $salesChannelContext): string
    {
        $customer = $salesChannelContext->getCustomer();
        if (!$customer || $customer->getGuest()) {
            throw CustomerException::customerNotLoggedIn();
        }

        $vault = $this->vaultTokenService->getCustomerToken($customer->getId(), $salesChannelContext->getPaymentMethod()->getId(), $salesChannelContext->getContext());
        if ($vault === null) {
            throw new MissingCustomerVaultTokenException($customer->getId());
        }

        return $vault->getToken();
    }
}

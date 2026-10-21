<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenCollection;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
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
     *
     * @param EntityRepository<VaultTokenCollection> $vaultTokenRepository
     */
    public function __construct(
        private readonly TokenResource $tokenResource,
        private readonly EntityRepository $vaultTokenRepository,
        private readonly PaymentMethodDataRegistry $paymentMethodDataRegistry,
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
                description: 'Binds the token to the saved PayPal wallet of the logged-in customer, so the PayPal JS SDK can show it and let the customer change its funding instrument',
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

        $vaultId = $this->getPayPalVaultId($salesChannelContext);

        return new TokenResponse($this->tokenResource->getVaultClientToken($salesChannelContext, $vaultId)->getAccessToken());
    }

    private function getPayPalVaultId(SalesChannelContext $salesChannelContext): string
    {
        $customer = $salesChannelContext->getCustomer();
        if (!$customer || $customer->getGuest()) {
            throw CustomerException::customerNotLoggedIn();
        }

        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod(PayPalMethodData::class),
            $salesChannelContext->getContext(),
        );

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('mainMapping.paymentMethodId', $paymentMethodId));

        $vault = $this->vaultTokenRepository->search($criteria, $salesChannelContext->getContext())->getEntities()->first();
        if ($vault === null) {
            throw new MissingCustomerVaultTokenException($customer->getId());
        }

        return $vault->getToken();
    }
}

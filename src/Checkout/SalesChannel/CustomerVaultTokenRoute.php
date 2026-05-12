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
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\MissingCustomerVaultTokenException;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class CustomerVaultTokenRoute
{
    /**
     * @internal
     */
    public function __construct(
        private EntityRepository $vaultRepository,
        private TokenResourceInterface $tokenResource,
    ) {
    }

    #[OA\Get(
        path: '/paypal/vault-token',
        operationId: 'getPayPalCustomerVaultToken',
        description: 'Tries to get the customer vault token',
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The customer vault token',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'token',
                type: 'string'
            )])
        )],
    )]
    #[Route(path: '/store-api/paypal/vault-token', name: 'store-api.paypal.vault.token', defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => false], methods: ['GET'])]
    public function getVaultToken(SalesChannelContext $context): TokenResponse
    {
        $customer = $context->getCustomer();
        if (!$customer || $customer->getGuest()) {
            throw CustomerException::customerNotLoggedIn();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mainMapping.customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('mainMapping.paymentMethodId', $context->getPaymentMethod()->getId()));

        /** @var VaultTokenEntity|null $vault */
        $vault = $this->vaultRepository->search($criteria, $context->getContext())->first();

        $token = $this->tokenResource->getUserIdToken($context->getSalesChannelId(), $vault?->getTokenCustomer())->getIdToken();

        if ($token === null) {
            throw new MissingCustomerVaultTokenException($customer->getId());
        }

        return new TokenResponse($token);
    }
}

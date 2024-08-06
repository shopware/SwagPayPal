<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ClearVaultRoute extends AbstractClearVaultRoute
{
    /**
     * @param EntityRepository<VaultTokenMappingCollection> $tokenMappingRepository
     *
     * @internal
     */
    public function __construct(
        private EntityRepository $tokenMappingRepository,
    ) {
    }

    public function getDecorated(): AbstractClearVaultRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/store-api/paypal/vault/clear',
        operationId: 'paypalVaultClear',
        description: 'Clears the vault for the current customer',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'type', type: 'string', enum: ['cancel', 'browser', 'error']),
        ])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Vault has been cleared successfully',
        )]
    )]
    #[Route(path: '/store-api/paypal/vault/clear', name: 'store-api.paypal.vault.clear', methods: ['POST'], defaults: ['_loginRequired' => true])]
    public function clearVault(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $this->tokenMappingRepository->delete([[
            'customerId' => $salesChannelContext->getCustomerId(),
            'paymentMethodId' => $salesChannelContext->getPaymentMethod()->getId(),
        ]], $salesChannelContext->getContext());

        return new NoContentResponse();
    }
}

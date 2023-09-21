<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\DataAbstractionLayer\VaultTokenMapping\VaultTokenMappingCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    /**
     * @Since("8.0.0")
     */
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

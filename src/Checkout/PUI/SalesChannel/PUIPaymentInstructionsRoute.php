<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class PUIPaymentInstructionsRoute extends AbstractPUIPaymentInstructionsRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PUIInstructionsFetchService $instructionsService,
    ) {
    }

    public function getDecorated(): AbstractPUIPaymentInstructionsRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @throws ShopwareHttpException
     */
    #[OA\Get(
        path: '/store-api/paypal/pui/payment-instructions/{transactionId}',
        operationId: 'getPUIPaymentInstructions',
        description: 'Tries to get payment instructions for PUI payments',
        tags: ['Store API', 'PayPal'],
        parameters: [new OA\Parameter(
            name: 'transactionId',
            description: 'Identifier of the order transaction to be fetched',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The payment instructions of the order'
        )]
    )]
    #[Route(path: '/store-api/paypal/pui/payment-instructions/{transactionId}', name: 'store-api.paypal.pui.payment_instructions', defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true], methods: ['GET'])]
    public function getPaymentInstructions(string $transactionId, SalesChannelContext $salesChannelContext): PUIPaymentInstructionsResponse
    {
        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search(
            (new Criteria([$transactionId]))->addAssociation('stateMachineState'),
            $salesChannelContext->getContext()
        )->first();

        if ($transaction === null) {
            throw OrderException::orderTransactionNotFound($transactionId);
        }

        $instructions = $this->instructionsService->fetchPUIInstructions(
            $transaction,
            $salesChannelContext->getSalesChannel()->getId(),
            $salesChannelContext->getContext(),
        );

        return new PUIPaymentInstructionsResponse($instructions);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Dispute\Administration;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Dispute\Exception\NotAuthorizedException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\Disputes;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class DisputeController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DisputeResource $disputeResource,
    ) {
    }

    #[OA\Get(
        path: '/api/paypal/dispute',
        operationId: 'disputeList',
        description: 'Loads a list of PayPal disputes',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                description: 'ID of the sales channel to which the disputes belong',
                in: 'query',
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'disputeStateFilter',
                name: 'disputeStateFilter',
                description: "Filter for dispute state. Separate multiple states with a comma. Must one of these values: Swag\PayPal\RestApi\V1\Api\Disputes\Item::DISPUTE_STATES",
                in: 'query',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'List of PayPal disputes',
            content: new OA\JsonContent(ref: Disputes::class)
        )]
    )]
    #[Route(path: '/api/paypal/dispute', name: 'api.paypal.dispute_list', defaults: ['_acl' => ['swag_paypal_disputes.viewer']], methods: ['GET'])]
    public function disputeList(Request $request): JsonResponse
    {
        $salesChannelId = $this->validateSalesChannelId($request);
        $disputeStateFilter = $this->validateDisputeStateFilter($request);

        try {
            $disputeList = $this->disputeResource->list($salesChannelId, $disputeStateFilter);

            return new JsonResponse($disputeList);
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                throw new NotAuthorizedException();
            }

            throw $e;
        }
    }

    #[OA\Get(
        path: '/api/paypal/dispute/{disputeId}',
        operationId: 'disputeDetails',
        description: 'Loads the dispute details of the given PayPal dispute ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                description: 'ID of the sales channel to which the disputes belong',
                in: 'query',
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'disputeId',
                name: 'disputeId',
                description: 'ID of the dispute',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal dispute',
            content: new OA\JsonContent(ref: Item::class)
        )]
    )]
    #[Route(path: '/api/paypal/dispute/{disputeId}', name: 'api.paypal.dispute_details', defaults: ['_acl' => ['swag_paypal_disputes.viewer']], methods: ['GET'])]
    public function disputeDetails(string $disputeId, Request $request): JsonResponse
    {
        $salesChannelId = $this->validateSalesChannelId($request);
        $dispute = $this->disputeResource->get($disputeId, $salesChannelId);

        return new JsonResponse($dispute);
    }

    /**
     * @throws InvalidSalesChannelIdException
     */
    private function validateSalesChannelId(Request $request): ?string
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId');
        if ($salesChannelId === '') {
            return null;
        }

        if (Uuid::isValid($salesChannelId) === false) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannelId;
    }

    /**
     * @throws RoutingException
     */
    private function validateDisputeStateFilter(Request $request): ?string
    {
        $disputeStateFilter = $request->query->get('disputeStateFilter');
        if (!\is_string($disputeStateFilter)) {
            return null;
        }

        foreach (\explode(',', $disputeStateFilter) as $disputeStateFilterItem) {
            if (!\in_array($disputeStateFilterItem, Item::DISPUTE_STATES, true)) {
                throw RoutingException::invalidRequestParameter('disputeStateFilter');
            }
        }

        return $disputeStateFilter;
    }
}

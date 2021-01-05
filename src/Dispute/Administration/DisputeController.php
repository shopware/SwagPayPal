<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Dispute\Administration;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class DisputeController extends AbstractController
{
    /**
     * @var DisputeResource
     */
    private $disputeResource;

    public function __construct(DisputeResource $disputeResource)
    {
        $this->disputeResource = $disputeResource;
    }

    /**
     * @OA\Get(
     *     path="/paypal/dispute",
     *     description="Loads a list of PayPal disputes",
     *     operationId="disputeList",
     *     tags={"Admin API", "PayPal", "Disputes"},
     *     @OA\Parameter(
     *         parameter="salesChannelId",
     *         name="salesChannelId",
     *         in="query",
     *         description="ID of the sales channel to which the disputes belong",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="disputeStateFilter",
     *         name="disputeStateFilter",
     *         in="query",
     *         description="Filter for dispute state. Seperate multiple states with a comma. Must one of these values: Swag\PayPal\RestApi\V1\Api\Disputes\Item::DISPUTE_STATES",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="List of PayPal disputes",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal/dispute",
     *      name="api.paypal.dispute_list",
     *      methods={"GET"}
     * )
     * @Acl({"swag_paypal_disputes.viewer"})
     */
    public function disputeList(Request $request): JsonResponse
    {
        $salesChannelId = $this->validateSalesChannelId($request);
        $disputeStateFilter = $this->validateDisputeStateFilter($request);

        $disputeList = $this->disputeResource->list($salesChannelId, $disputeStateFilter);

        return new JsonResponse($disputeList);
    }

    /**
     * @OA\Get(
     *     path="/paypal/dispute/{disputeId}",
     *     description="Loads the dispute details of the given PayPal dispute ID",
     *     operationId="disputeDetails",
     *     tags={"Admin API", "PayPal", "Disputes"},
     *     @OA\Parameter(
     *         parameter="disputeId",
     *         name="disputeId",
     *         in="path",
     *         description="ID of the dispute",
     *         @OA\Schema(type="string"),
     *         required=true
     *     ),
     *     @OA\Parameter(
     *         parameter="salesChannelId",
     *         name="salesChannelId",
     *         in="query",
     *         description="ID of the sales channel to which the dispute belongs",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal dispute",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal/dispute/{disputeId}",
     *      name="api.paypal.dispute_details",
     *      methods={"GET"}
     * )
     * @Acl({"swag_paypal_disputes.viewer"})
     */
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
        $salesChannelId = $request->query->get('salesChannelId');
        if ($salesChannelId !== null) {
            $salesChannelId = (string) $salesChannelId;
        }

        if ($salesChannelId !== null && Uuid::isValid($salesChannelId) === false) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannelId;
    }

    /**
     * @throws InvalidRequestParameterException
     */
    private function validateDisputeStateFilter(Request $request): ?string
    {
        $disputeStateFilter = $request->query->get('disputeStateFilter');
        if ($disputeStateFilter !== null) {
            $disputeStateFilter = (string) $disputeStateFilter;
            foreach (\explode(',', $disputeStateFilter) as $disputeStateFilterItem) {
                if (!\in_array($disputeStateFilterItem, Item::DISPUTE_STATES, true)) {
                    throw new InvalidRequestParameterException('disputeStateFilter');
                }
            }
        }

        return $disputeStateFilter;
    }
}

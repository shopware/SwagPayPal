<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Dispute\Administration;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class DisputeController extends AbstractController
{
    private DisputeResource $disputeResource;

    /**
     * @internal
     */
    public function __construct(DisputeResource $disputeResource)
    {
        $this->disputeResource = $disputeResource;
    }

    /**
     * @Since("2.2.0")
     *
     * @OA\Get(
     *     path="/paypal/dispute",
     *     description="Loads a list of PayPal disputes",
     *     operationId="disputeList",
     *     tags={"Admin API", "PayPal"},
     *
     *     @OA\Parameter(
     *         parameter="salesChannelId",
     *         name="salesChannelId",
     *         in="query",
     *         description="ID of the sales channel to which the disputes belong",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         parameter="disputeStateFilter",
     *         name="disputeStateFilter",
     *         in="query",
     *         description="Filter for dispute state. Seperate multiple states with a comma. Must one of these values: Swag\PayPal\RestApi\V1\Api\Disputes\Item::DISPUTE_STATES",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="List of PayPal disputes",
     *
     *         @OA\JsonContent(ref="#/components/schemas/swag_paypal_v1_disputes")
     *     )
     * )
     *
     * @Route(
     *     "/api/paypal/dispute",
     *      name="api.paypal.dispute_list",
     *      methods={"GET"},
     *      defaults={"_acl": {"swag_paypal_disputes.viewer"}}
     * )
     */
    public function disputeList(Request $request): JsonResponse
    {
        $salesChannelId = $this->validateSalesChannelId($request);
        $disputeStateFilter = $this->validateDisputeStateFilter($request);

        $disputeList = $this->disputeResource->list($salesChannelId, $disputeStateFilter);

        return new JsonResponse($disputeList);
    }

    /**
     * @Since("2.2.0")
     *
     * @OA\Get(
     *     path="/paypal/dispute/{disputeId}",
     *     description="Loads the dispute details of the given PayPal dispute ID",
     *     operationId="disputeDetails",
     *     tags={"Admin API", "PayPal"},
     *
     *     @OA\Parameter(
     *         parameter="disputeId",
     *         name="disputeId",
     *         in="path",
     *         description="ID of the dispute",
     *
     *         @OA\Schema(type="string"),
     *         required=true
     *     ),
     *
     *     @OA\Parameter(
     *         parameter="salesChannelId",
     *         name="salesChannelId",
     *         in="query",
     *         description="ID of the sales channel to which the dispute belongs",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal dispute",
     *
     *         @OA\JsonContent(ref="#/components/schemas/swag_paypal_v1_disputes_item")
     *     )
     * )
     *
     * @Route(
     *     "/api/paypal/dispute/{disputeId}",
     *      name="api.paypal.dispute_details",
     *      methods={"GET"},
     *      defaults={"_acl": {"swag_paypal_disputes.viewer"}}
     * )
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
        if ($salesChannelId === null) {
            return null;
        }

        if (!\is_string($salesChannelId)) {
            if (\class_exists(RoutingException::class)) {
                throw RoutingException::invalidRequestParameter('salesChannelId');
            } else {
                /** @phpstan-ignore-next-line remove condition and keep if branch with min-version 6.5.2.0 */
                throw new InvalidRequestParameterException('salesChannelId');
            }
        }

        if (Uuid::isValid($salesChannelId) === false) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannelId;
    }

    /**
     * @throws InvalidRequestParameterException
     */
    private function validateDisputeStateFilter(Request $request): ?string
    {
        /** @var string|int|float|null $disputeStateFilter */ // Remove once SW 6.4.3.0 is min version
        $disputeStateFilter = $request->query->get('disputeStateFilter');
        if ($disputeStateFilter === null) {
            return null;
        }

        if (!\is_string($disputeStateFilter)) {
            if (\class_exists(RoutingException::class)) {
                throw RoutingException::invalidRequestParameter('disputeStateFilter');
            } else {
                /** @phpstan-ignore-next-line remove condition and keep if branch with min-version 6.5.2.0 */
                throw new InvalidRequestParameterException('disputeStateFilter');
            }
        }

        foreach (\explode(',', $disputeStateFilter) as $disputeStateFilterItem) {
            if (!\in_array($disputeStateFilterItem, Item::DISPUTE_STATES, true)) {
                if (\class_exists(RoutingException::class)) {
                    throw RoutingException::invalidRequestParameter('disputeStateFilter');
                } else {
                    /** @phpstan-ignore-next-line remove condition and keep if branch with min-version 6.5.2.0 */
                    throw new InvalidRequestParameterException('disputeStateFilter');
                }
            }
        }

        return $disputeStateFilter;
    }
}

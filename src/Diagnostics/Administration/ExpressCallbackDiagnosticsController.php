<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Administration;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackDiagnostics;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackResult;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class ExpressCallbackDiagnosticsController extends AbstractController
{
    public function __construct(private readonly ExpressCallbackDiagnostics $expressCallbackDiagnostics)
    {
    }

    #[OA\Get(
        path: '/_action/paypal/diagnostics/express-callback/{salesChannelId}',
        operationId: 'getPayPalExpressCallbackDiagnostics',
        description: 'Checks whether PayPal can reach the shop with the shipping callback of Express Checkout',
        tags: ['Admin Api', 'PayPal'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The state of the shipping callback',
            content: new OA\JsonContent(ref: ExpressCallbackResult::class),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/express-callback/{salesChannelId}',
        name: 'api.action.paypal.diagnostics.express_callback',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['GET'],
    )]
    public function check(string $salesChannelId, Request $request, Context $context): JsonResponse
    {
        return new JsonResponse($this->expressCallbackDiagnostics->check($salesChannelId, $context, $this->isBehindUntrustedProxy($request)));
    }

    #[OA\Post(
        path: '/_action/paypal/diagnostics/express-callback/{salesChannelId}/reachability',
        operationId: 'testPayPalExpressCallbackReachability',
        description: 'Sends a test request to the shipping callback URLs of the Sales Channel, like PayPal sends it. It runs from the shop\'s server, so a pass means the URL answers from there',
        tags: ['Admin Api', 'PayPal'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The answer of each callback URL',
            content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: ReachabilityResult::class)),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/express-callback/{salesChannelId}/reachability',
        name: 'api.action.paypal.diagnostics.express_callback.reachability',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['POST'],
    )]
    public function testReachability(string $salesChannelId, Context $context): JsonResponse
    {
        return new JsonResponse($this->expressCallbackDiagnostics->testReachability($salesChannelId, $context));
    }

    private function isBehindUntrustedProxy(Request $request): bool
    {
        $forwardedProto = \strtolower((string) $request->headers->get('X-Forwarded-Proto'));

        return \str_starts_with($forwardedProto, 'https') && !$request->isSecure();
    }
}

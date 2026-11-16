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
use Swag\PayPal\Diagnostics\Twig\TwigDiagnostics;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnosticsResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class TwigDiagnosticsController extends AbstractController
{
    public function __construct(private readonly TwigDiagnostics $twigDiagnostics)
    {
    }

    #[OA\Get(
        path: '/_action/paypal/diagnostics/twig/{salesChannelId}',
        operationId: 'getPayPalTwigDiagnostics',
        description: 'Checks whether templates of themes, extensions and apps keep the checked blocks of PayPal\'s storefront and document templates from rendering',
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
            description: 'The templates that keep checked PayPal blocks from rendering',
            content: new OA\JsonContent(ref: TwigDiagnosticsResult::class),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/twig/{salesChannelId}',
        name: 'api.action.paypal.diagnostics.twig',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['GET'],
    )]
    public function check(string $salesChannelId, Context $context): JsonResponse
    {
        return new JsonResponse($this->twigDiagnostics->check($salesChannelId, $context));
    }
}

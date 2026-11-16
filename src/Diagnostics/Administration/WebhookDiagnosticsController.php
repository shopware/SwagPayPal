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
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Diagnostics\Webhook\WebhookDiagnostics;
use Swag\PayPal\Diagnostics\Webhook\WebhookResult;
use Swag\PayPal\Diagnostics\Webhook\WebhookTestTarget;
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
class WebhookDiagnosticsController extends AbstractController
{
    public function __construct(private readonly WebhookDiagnostics $webhookDiagnostics)
    {
    }

    #[OA\Get(
        path: '/_action/paypal/diagnostics/webhook/{salesChannelId}',
        operationId: 'getPayPalWebhookDiagnostics',
        description: 'Checks whether PayPal can reach the shop with webhooks',
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
            description: 'The state of the webhook',
            content: new OA\JsonContent(ref: WebhookResult::class),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/webhook/{salesChannelId}',
        name: 'api.action.paypal.diagnostics.webhook',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['GET'],
    )]
    public function check(string $salesChannelId, Context $context): JsonResponse
    {
        return new JsonResponse($this->webhookDiagnostics->check($salesChannelId, $context));
    }

    #[OA\Post(
        path: '/_action/paypal/diagnostics/webhook/{salesChannelId}/reachability',
        operationId: 'testPayPalWebhookReachability',
        description: 'Sends a test request to the webhook URL of the shop, like PayPal sends it. It runs from the shop\'s server, so a pass means the URL answers from there',
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
            description: 'The answer of the webhook URL',
            content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: ReachabilityResult::class)),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/webhook/{salesChannelId}/reachability',
        name: 'api.action.paypal.diagnostics.webhook.reachability',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['POST'],
    )]
    public function testReachability(string $salesChannelId, Context $context): JsonResponse
    {
        return new JsonResponse($this->webhookDiagnostics->testReachability($salesChannelId, $context));
    }

    #[OA\Post(
        path: '/_action/paypal/diagnostics/webhook/{salesChannelId}/test',
        operationId: 'sendPayPalTestWebhook',
        description: 'Makes PayPal send a test webhook to the registered URL of the webhook (`registered`) or to a URL the plugin would register: with the host the Administration is opened with (`administration`) or with Shopware\'s `APP_URL` (`app_url`). Poll `GET` on the same path to see whether it arrived',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['target'],
                properties: [new OA\Property(property: 'target', description: 'Where PayPal sends the test webhook', type: 'string', enum: ['registered', 'administration', 'app_url'])],
            ),
        ),
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
            description: 'The test webhook PayPal was asked to send',
            content: new OA\JsonContent(ref: TestWebhook::class),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/webhook/{salesChannelId}/test',
        name: 'api.action.paypal.diagnostics.webhook.test',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['POST'],
    )]
    public function sendTestWebhook(string $salesChannelId, Request $request, Context $context): JsonResponse
    {
        $target = $request->request->getString('target');
        if ($target === '') {
            throw RoutingException::missingRequestParameter('target');
        }

        $target = WebhookTestTarget::tryFrom($target) ?? throw RoutingException::invalidRequestParameter('target');

        return new JsonResponse($this->webhookDiagnostics->sendTestWebhook($salesChannelId, $target, $context));
    }

    #[OA\Get(
        path: '/_action/paypal/diagnostics/webhook/{salesChannelId}/test',
        operationId: 'getPayPalTestWebhookArrival',
        description: 'Whether the last test webhook arrived since PayPal was asked to send it. Doesn\'t call PayPal',
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
            description: 'Whether the test webhook arrived',
            content: new OA\JsonContent(
                required: ['arrived'],
                properties: [new OA\Property(property: 'arrived', type: 'boolean')],
            ),
        )],
    )]
    #[Route(
        path: '/api/_action/paypal/diagnostics/webhook/{salesChannelId}/test',
        name: 'api.action.paypal.diagnostics.webhook.test_arrival',
        requirements: ['salesChannelId' => '[0-9a-f]{32}'],
        defaults: ['_acl' => ['swag_paypal.viewer']],
        methods: ['GET'],
    )]
    public function getTestWebhookArrival(string $salesChannelId, Context $context): JsonResponse
    {
        return new JsonResponse(['arrived' => $this->webhookDiagnostics->hasTestWebhookArrived($salesChannelId, $context)]);
    }
}

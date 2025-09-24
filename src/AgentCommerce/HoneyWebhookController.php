<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class HoneyWebhookController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly HoneyWebhookService $webhookService,
    ) {
    }

    #[OA\Post(
        path: '/_action/paypal/honey/webhook/register/{salesChannelId}',
        operationId: 'registerHoneyWebhook',
        tags: ['Admin Api', 'SwagPayPalWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns the action taken for the webhook registration',
            content: new OA\JsonContent(properties: [
                new OA\Property(
                    property: 'success',
                    type: 'boolean',
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                ),
            ])
        )]
    )]
    #[Route(path: '/api/_action/paypal/honey/webhook/register/{salesChannelId}', name: 'api.action.paypal.honey.webhook.register', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function registerWebhook(string $salesChannelId, Context $context): ResponseInterface
    {
        return $this->webhookService->register($salesChannelId, $context);
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WebhookServiceInterface $webhookService,
        private readonly EntityRepository $systemConfigRepository,
    ) {
    }

    #[OA\Get(
        path: '/api/_action/paypal/webhook/status/{salesChannelId}',
        operationId: 'getWebhookStatus',
        tags: ['Admin Api', 'SwagPayPalWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns the status of the PayPal webhook',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'result',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/webhook/status/{salesChannelId}', name: 'api.action.paypal.webhook.status', methods: ['GET'], defaults: ['_acl' => ['swag_paypal.viewer']])]
    public function statusWebhook(string $salesChannelId): JsonResponse
    {
        $status = $this->webhookService->getStatus($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $status]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/webhook/register/{salesChannelId}',
        operationId: 'registerWebhook',
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
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'result',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/webhook/register/{salesChannelId}', name: 'api.action.paypal.webhook.register', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function registerWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->registerWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    #[OA\Delete(
        path: '/api/_action/paypal/webhook/deregister/{salesChannelId}',
        operationId: 'deregisterWebhook',
        tags: ['Admin Api', 'SwagPayPalWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns the action taken for the webhook deregistration',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'result',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/webhook/deregister/{salesChannelId}', name: 'api.action.paypal.webhook.deregister', methods: ['DELETE'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function deregisterWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->deregisterWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/webhook/execute',
        operationId: 'executeWebhook',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: Webhook::class)),
        tags: ['Admin Api', 'SwagPayPalPosWebhook'],
        parameters: [new OA\Parameter(
            parameter: WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME,
            name: WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME,
            in: 'query',
            schema: new OA\Schema(type: 'string')
        )],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Webhook execution was successful')]
    )]
    #[Route(path: '/api/_action/paypal/webhook/execute', name: 'api.action.paypal.webhook.execute', methods: ['POST'], defaults: ['auth_required' => false])]
    public function executeWebhook(Request $request, Context $context): Response
    {
        $token = $this->getShopwareToken($request);
        $this->validateShopwareToken($token, $context);

        $webhook = $this->createWebhookFromPostData($request);
        $this->tryToExecuteWebhook($context, $webhook);

        return new Response();
    }

    /**
     * @throws BadRequestHttpException
     */
    protected function createWebhookFromPostData(Request $request): Webhook
    {
        $postData = $request->request->all();
        $this->logger->debug('Received webhook', ['payload' => $postData]);

        if (empty($postData)) {
            throw new BadRequestHttpException('No webhook data sent');
        }

        $webhook = new Webhook();
        $webhook->assign($postData);

        return $webhook;
    }

    /**
     * @throws BadRequestHttpException
     * @throws PayPalApiException
     */
    protected function tryToExecuteWebhook(Context $context, Webhook $webhook): void
    {
        $logContext = ['type' => $webhook->getEventType(), 'webhook' => \json_encode($webhook)];

        try {
            $this->webhookService->executeWebhook($webhook, $context);
            $this->logger->info('[PayPal Webhook] Webhook successfully executed', $logContext);
        } catch (WebhookHandlerNotFoundException $exception) {
            $this->logger->info(\sprintf('[PayPal Webhook] %s', $exception->getMessage()), $logContext);
        } catch (WebhookException $webhookException) {
            $this->logger->error(\sprintf('[PayPal Webhook] %s', $webhookException->getMessage()), $logContext);

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            if ($e instanceof PayPalApiException && $e->is(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND)) {
                $this->logger->warning(\sprintf('[PayPal Webhook] %s', $e->getMessage()), $logContext);

                return;
            }

            $this->logger->error(
                \sprintf('[PayPal Webhook] %s', $e->getMessage()),
                [...$logContext, 'error' => $e],
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    private function getShopwareToken(Request $request): string
    {
        $token = $request->query->getAlnum(WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME);
        if ($token === '') {
            throw new BadRequestHttpException('Shopware token is invalid');
        }

        return $token;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function validateShopwareToken(string $token, Context $context): void
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('configurationValue', $token));
        /** @var SystemConfigCollection $systemConfigCollection */
        $systemConfigCollection = $this->systemConfigRepository->search($criteria, $context)->getEntities();

        foreach ($systemConfigCollection as $systemConfigEntity) {
            if ($systemConfigEntity->getConfigurationKey() === Settings::WEBHOOK_EXECUTE_TOKEN) {
                return;
            }
        }

        throw new BadRequestHttpException('Shopware token is invalid');
    }
}

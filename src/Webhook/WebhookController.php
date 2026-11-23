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
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Exception\ParentPaymentNotFoundException;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionInvalidIdException;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
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
        path: '/_action/paypal/webhook/status/{salesChannelId}',
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
        path: '/_action/paypal/webhook/register/{salesChannelId}',
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
        path: '/_action/paypal/webhook/deregister/{salesChannelId}',
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
        path: '/_action/paypal/webhook/execute',
        operationId: 'executeWebhook',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: Event::class)),
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
        $token = $request->query->getAlnum(WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME);
        if (!$this->validateShopwareToken($token, $context)) {
            if ($id = $request->request->get('id')) {
                // unauthenticated input, `Log::context()` cuts it
                $this->logger->warning('Rejected a PayPal webhook event with a token that doesn\'t match this shop', Log::context(
                    code: LogCode::WEBHOOK_TOKEN_INVALID,
                    webhookEventId: (string) $id,
                    webhookEventType: (string) $request->request->get('event_type'),
                ));
            }

            return new Response('Shopware token is invalid', Response::HTTP_BAD_REQUEST);
        }

        $webhook = $this->createWebhookFromPostData($request);

        try {
            $this->tryToExecuteWebhook($context, $webhook);
        } catch (BadRequestHttpException $e) {
            // errors are already logged, no need to throw this exception
            return new Response($e->getMessage(), $e->getStatusCode());
        }

        return new Response();
    }

    /**
     * @throws BadRequestHttpException
     */
    protected function createWebhookFromPostData(Request $request): Event
    {
        $postData = $request->request->all();

        $webhook = new Event();
        $webhook->assign($postData);

        $this->logger->debug('Received a PayPal webhook event', Log::context(webhookEvent: $webhook, payload: $postData));

        if ($postData === []) {
            throw new BadRequestHttpException('No webhook data sent');
        }

        return $webhook;
    }

    /**
     * @throws BadRequestHttpException
     * @throws PayPalApiException
     */
    protected function tryToExecuteWebhook(Context $context, Event $webhook): void
    {
        $logContext = [Log::WEBHOOK_EVENT => $webhook];

        try {
            $this->webhookService->executeWebhook($webhook, $context);

            $this->logger->info('Processed PayPal webhook event {webhookEventType}', Log::context(...$logContext, code: LogCode::WEBHOOK_EVENT_PROCESSED));
        } catch (WebhookHandlerNotFoundException $exception) {
            $this->logger->debug(
                'Ignored PayPal webhook event {webhookEventType}: the plugin doesn\'t handle this event type',
                Log::context(...$logContext, exception: $exception),
            );
        } catch (WebhookOrderTransactionInvalidIdException $exception) {
            $this->logger->notice(
                'Ignored PayPal webhook event {webhookEventType}: it belongs to a PayPal order this shop didn\'t create',
                Log::context(...$logContext, code: LogCode::WEBHOOK_EVENT_UNRELATED, exception: $exception),
            );
        } catch (WebhookOrderTransactionNotFoundException|ParentPaymentNotFoundException $webhookException) {
            $this->logger->warning(
                'Could not match PayPal webhook event {webhookEventType} to an order of this shop, PayPal sends it again up to 25 times over 3 days',
                Log::context(...$logContext, code: LogCode::WEBHOOK_TRANSACTION_UNKNOWN, exception: $webhookException),
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (WebhookException $webhookException) {
            $this->logger->error(
                'Could not process PayPal webhook event {webhookEventType}: the event lacks the data the plugin needs',
                Log::context(...$logContext, code: LogCode::WEBHOOK_EVENT_INVALID, exception: $webhookException),
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            if ($e instanceof PayPalApiException && $e->is(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND)) {
                $this->logger->warning(
                    'Could not process PayPal webhook event {webhookEventType}: PayPal couldn\'t find the referenced resource',
                    Log::context(...$logContext, code: LogCode::WEBHOOK_RESOURCE_NOT_FOUND, exception: $e),
                );

                return;
            }

            $this->logger->error(
                'Could not process PayPal webhook event {webhookEventType}, PayPal sends it again up to 25 times over 3 days',
                Log::context(...$logContext, code: LogCode::WEBHOOK_EVENT_FAILED, exception: $e),
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }

    private function validateShopwareToken(string $token, Context $context): bool
    {
        if ($token === '') {
            return false;
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('configurationValue', $token));
        /** @var SystemConfigCollection $systemConfigCollection */
        $systemConfigCollection = $this->systemConfigRepository->search($criteria, $context)->getEntities();

        foreach ($systemConfigCollection as $systemConfigEntity) {
            if ($systemConfigEntity->getConfigurationKey() === Settings::WEBHOOK_EXECUTE_TOKEN) {
                return true;
            }
        }

        return false;
    }
}

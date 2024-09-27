<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Webhook\Webhook;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookHandlerNotFoundException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController extends AbstractController
{
    use PosSalesChannelTrait;

    /**
     * @internal
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WebhookService $webhookService,
        private readonly EntityRepository $salesChannelRepository,
    ) {
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}',
        operationId: 'registerPosWebhook',
        tags: ['Admin Api', 'SwagPayPalPosWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Webhook registration was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}', name: 'api.action.paypal.pos.webhook.registration.register', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function registerWebhook(string $salesChannelId, Context $context): Response
    {
        $this->webhookService->registerWebhook($salesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Delete(
        path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}',
        operationId: 'deregisterPosWebhook',
        tags: ['Admin Api', 'SwagPayPalPosWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Webhook deregistration was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}', name: 'api.action.paypal.pos.webhook.registration.unregister', methods: ['DELETE'], defaults: ['_acl' => ['sales_channel.deleter']])]
    public function unregisterWebhook(string $salesChannelId, Context $context): Response
    {
        $this->webhookService->unregisterWebhook($salesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/webhook/execute/{salesChannelId}',
        operationId: 'executePosWebhook',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: Webhook::class)),
        tags: ['Admin Api', 'SwagPayPalPosWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'salesChannelId',
            name: 'salesChannelId',
            in: 'path',
            schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
        )],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Webhook execution was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/webhook/execute/{salesChannelId}', name: 'api.action.paypal.pos.webhook.execute', methods: ['POST'], defaults: ['auth_required' => false])]
    public function executeWebhook(string $salesChannelId, Request $request, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $webhook = $this->createWebhookFromPostData($request);

        if ($webhook->getEventName() !== WebhookEventNames::TEST_MESSAGE) {
            $this->validateSignature($request, $webhook, $salesChannel);
            $this->tryToExecuteWebhook($webhook, $salesChannel, $context);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws UnauthorizedHttpException
     */
    private function validateSignature(Request $request, Webhook $webhook, SalesChannelEntity $salesChannel): void
    {
        $signature = $request->headers->get('x-izettle-signature');

        if (!$signature) {
            throw new UnauthorizedHttpException('Request not signed');
        }

        $signingKey = $this->getPosSalesChannel($salesChannel)->getWebhookSigningKey();

        if (!$signingKey) {
            throw new WebhookNotRegisteredException($salesChannel->getId());
        }

        $payloadToSign = \stripslashes($webhook->getTimestamp() . '.' . $webhook->getPayload());
        $generatedSignature = \hash_hmac('sha256', $payloadToSign, $signingKey);

        if (\hash_equals($generatedSignature, $signature) === false) {
            throw new UnauthorizedHttpException('Signature is invalid');
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    private function createWebhookFromPostData(Request $request): Webhook
    {
        $postData = $request->request->all();

        $this->logger->debug('[Zettle Webhook] Received webhook', ['payload' => $postData]);

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
    private function tryToExecuteWebhook(Webhook $webhook, SalesChannelEntity $salesChannel, Context $context): void
    {
        $logContext = ['type' => $webhook->getEventName(), 'webhook' => \json_encode($webhook)];

        try {
            $this->webhookService->executeWebhook($webhook, $salesChannel, $context);
            $this->logger->info('[Zettle Webhook] Webhook successfully executed', $logContext);
        } catch (WebhookHandlerNotFoundException $exception) {
            $this->logger->info(\sprintf('[Zettle Webhook] %s', $exception->getMessage()), $logContext);
        } catch (WebhookException $webhookException) {
            $this->logger->error(\sprintf('[Zettle Webhook] %s', $webhookException->getMessage()), $logContext);

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Throwable $e) {
            if ($e instanceof PayPalApiException && $e->is(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND)) {
                $this->logger->warning(\sprintf('[Zettle Webhook] %s', $e->getMessage()), $logContext);

                return;
            }

            $this->logger->error(
                \sprintf('[Zettle Webhook] %s', $e->getMessage()),
                [...$logContext, 'error' => $e],
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new WebhookIdInvalidException($salesChannelId);
        }

        return $salesChannel;
    }
}

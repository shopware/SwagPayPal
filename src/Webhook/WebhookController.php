<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController extends AbstractController
{
    private LoggerInterface $logger;

    private WebhookServiceInterface $webhookService;

    private EntityRepository $systemConfigRepository;

    /**
     * @internal
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookServiceInterface $webhookService,
        EntityRepository $systemConfigRepository
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->systemConfigRepository = $systemConfigRepository;
    }

    #[Route(path: '/api/_action/paypal/webhook/status/{salesChannelId}', name: 'api.action.paypal.webhook.status', methods: ['GET'], defaults: ['_acl' => ['swag_paypal.viewer']])]
    public function statusWebhook(string $salesChannelId): JsonResponse
    {
        $status = $this->webhookService->getStatus($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $status]);
    }

    #[Route(path: '/api/_action/paypal/webhook/register/{salesChannelId}', name: 'api.action.paypal.webhook.register', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function registerWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->registerWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    #[Route(path: '/api/_action/paypal/webhook/deregister/{salesChannelId}', name: 'api.action.paypal.webhook.deregister', methods: ['DELETE'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function deregisterWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->deregisterWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

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
     *
     * @return WebhookV1|WebhookV2
     */
    protected function createWebhookFromPostData(Request $request): PayPalApiStruct
    {
        $postData = $request->request->all();
        $this->logger->debug('Received webhook', ['payload' => $postData]);

        if (empty($postData)) {
            throw new BadRequestHttpException('No webhook data sent');
        }

        if (isset($postData['resource_version']) && $postData['resource_version'] === '2.0') {
            $webhook = new WebhookV2();
        } else {
            $webhook = new WebhookV1();
        }

        $webhook->assign($postData);

        return $webhook;
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     *
     * @throws BadRequestHttpException
     */
    protected function tryToExecuteWebhook(Context $context, PayPalApiStruct $webhook): void
    {
        try {
            $this->webhookService->executeWebhook($webhook, $context);
        } catch (WebhookHandlerNotFoundException $exception) {
            $this->logger->info(\sprintf('[PayPal Webhook] %s', $exception->getMessage()), ['webhook', \json_encode($webhook)]);
        } catch (WebhookException $webhookException) {
            $logMessage = \sprintf('[PayPal Webhook] %s', $webhookException->getMessage());
            $logContext = ['type' => $webhookException->getEventType(), 'webhook' => \json_encode($webhook)];
            $this->logger->error($logMessage, $logContext);

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

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

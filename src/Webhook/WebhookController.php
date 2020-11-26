<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class WebhookController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WebhookServiceInterface
     */
    private $webhookService;

    /**
     * @var EntityRepositoryInterface
     */
    private $systemConfigRepository;

    public function __construct(
        LoggerInterface $logger,
        WebhookServiceInterface $webhookService,
        EntityRepositoryInterface $systemConfigRepository
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->systemConfigRepository = $systemConfigRepository;
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/webhook/register/{salesChannelId}",
     *     name="api.action.paypal.webhook.register",
     *     methods={"POST"}
     * )
     * @Acl({"swag_paypal.editor"})
     */
    public function registerWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->registerWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/webhook/deregister/{salesChannelId}",
     *     name="api.action.paypal.webhook.deregister",
     *     methods={"DELETE"}
     * )
     * @Acl({"swag_paypal.editor"})
     */
    public function deregisterWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->deregisterWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/webhook/execute",
     *     name="api.action.paypal.webhook.execute",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     */
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
        $this->logger->debug('[PayPal Webhook] Received webhook', ['payload' => $postData]);

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
        } catch (WebhookException $webhookException) {
            $logMessage = \sprintf('[PayPal Webhook] %s', $webhookException->getMessage());
            $logContext = ['type' => $webhookException->getEventType(), 'webhook' => \json_encode($webhook)];

            if ($webhookException instanceof WebhookHandlerNotFoundException) {
                $this->logger->info($logMessage, $logContext);
            } else {
                $this->logger->error($logMessage, $logContext);
            }

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            $this->logger->error(\sprintf('[PayPal Webhook] %s', $e->getMessage()));

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
            if ($systemConfigEntity->getConfigurationKey() === SettingsService::SYSTEM_CONFIG_DOMAIN . WebhookService::WEBHOOK_TOKEN_CONFIG_KEY) {
                return;
            }
        }

        throw new BadRequestHttpException('Shopware token is invalid');
    }
}

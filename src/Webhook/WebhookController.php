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
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Webhook\Exception\WebhookException;
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
     * @var WebhookServiceInterface|WebhookDeregistrationServiceInterface
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
        if ($this->deleteableWebhook()) {
            $result = $this->webhookService->deregisterWebhook($salesChannelId !== 'null' ? $salesChannelId : null);
        } else {
            $result = WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
        }

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
     * @deprecated tag:v2.0.0 - Will be removed. Use WebhookController::executeWebhook instead
     * @RouteScope(scopes={"storefront"})
     * @Route(
     *     "/paypal/webhook/execute",
     *     name="paypal.webhook.execute",
     *     methods={"POST"},
     *     defaults={"csrf_protected"=false}
     * )
     */
    public function executeWebhookDeprecated(Request $request, Context $context): Response
    {
        $this->logger->error(
            \sprintf('Route "paypal.webhook.execute" is deprecated. Use "api.action.paypal.webhook.execute" instead. Please save the PayPal settings to prevent this error.')
        );

        return $this->executeWebhook($request, $context);
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

    /**
     * @throws BadRequestHttpException
     */
    private function createWebhookFromPostData(Request $request): Webhook
    {
        $postData = $request->request->all();
        $this->logger->debug('[PayPal Webhook] Received webhook', ['payload' => $postData]);

        if (empty($postData)) {
            throw new BadRequestHttpException('No webhook data sent');
        }

        $webhook = new Webhook();
        $webhook->assign($postData);

        return $webhook;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function tryToExecuteWebhook(Context $context, Webhook $webhook): void
    {
        try {
            $this->webhookService->executeWebhook($webhook, $context);
        } catch (WebhookException $webhookException) {
            $this->logger->error(
                '[PayPal Webhook] ' . $webhookException->getMessage(),
                [
                    'type' => $webhookException->getEventType(),
                    'webhook' => \json_encode($webhook),
                ]
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            $this->logger->error('[PayPal Webhook] ' . $e->getMessage());

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }

    /**
     * @deprecated tag:v2.0.0 - will be removed
     */
    private function deleteableWebhook(): bool
    {
        try {
            new \ReflectionMethod($this->webhookService, 'deregisterWebhook');
        } catch (\ReflectionException $e) {
            // if deregister not exists
            return false;
        }

        return true;
    }
}

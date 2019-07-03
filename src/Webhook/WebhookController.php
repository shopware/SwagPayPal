<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

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
     * @var SettingsServiceInterface
     */
    private $settingsProvider;

    public function __construct(
        LoggerInterface $logger,
        WebhookServiceInterface $webhookService,
        SettingsServiceInterface $settingsProvider
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * @Route("/api/v{version}/_action/paypal/webhook/register/{salesChannelId}", name="api.action.paypal.webhook.register", methods={"POST"})
     */
    public function registerWebhook(string $salesChannelId): JsonResponse
    {
        $result = $this->webhookService->registerWebhook($salesChannelId !== 'null' ? $salesChannelId : null);

        return new JsonResponse(['result' => $result]);
    }

    /**
     * @Route("/paypal/webhook/execute", name="paypal.webhook.execute", methods={"POST"})
     *
     * @throws BadRequestHttpException
     */
    public function executeWebhook(Request $request, Context $context): Response
    {
        $token = $this->getShopwareToken($request);
        $this->validateShopwareToken($token);

        $webhook = $this->createWebhookFromPostData($request);
        $this->tryToExecuteWebhook($context, $webhook);

        return new Response();
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
     * @throws PayPalSettingsInvalidException
     */
    private function validateShopwareToken(string $token): void
    {
        // TODO: Get sales channel id
        $settings = $this->settingsProvider->getSettings();
        if ($token !== $settings->getWebhookExecuteToken()) {
            throw new BadRequestHttpException('Shopware token is invalid');
        }
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
                    'webhook' => json_encode($webhook),
                ]
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Exception $e) {
            $this->logger->error('[PayPal Webhook] ' . $e->getMessage());

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }
}

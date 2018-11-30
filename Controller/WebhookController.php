<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Setting\SettingsProviderInterface;
use SwagPayPal\Webhook\Exception\WebhookException;
use SwagPayPal\Webhook\WebhookServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends Controller
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
     * @var SettingsProviderInterface
     */
    private $settingsProvider;

    public function __construct(
        LoggerInterface $logger,
        WebhookServiceInterface $webhookService,
        SettingsProviderInterface $settingsProvider
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * @Route("/api/v{version}/_action/paypal/webhook/register", name="api.action.paypal.webhook.register", methods={"POST"})
     */
    public function registerWebhook(Context $context): JsonResponse
    {
        $result = $this->webhookService->registerWebhook($context);

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
        $this->validateShopwareToken($token, $context);

        $webhook = $this->createWebhookFromPostData($request);
        $this->tryToExecuteWebhook($context, $webhook);

        return new Response();
    }

    /**
     * @throws BadRequestHttpException
     */
    private function getShopwareToken(Request $request): string
    {
        $token = $request->query->getAlnum('sw-token');
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
        $settings = $this->settingsProvider->getSettings($context);
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
        } catch (Exception $e) {
            $this->logger->error('[PayPal Webhook] ' . $e->getMessage());

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        }
    }
}

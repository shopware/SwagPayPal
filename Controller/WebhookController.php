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
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Service\WebhookService;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Webhook\Exception\WebhookException;
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
     * @var WebhookService
     */
    private $webhookService;

    /**
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(
        LoggerInterface $logger,
        WebhookService $webhookService,
        RepositoryInterface $settingGeneralRepo
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->settingGeneralRepo = $settingGeneralRepo;
    }

    /**
     * @Route("/api/v{version}/paypal/webhook/register", name="paypal.webhook.register", methods={"POST"})
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
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settings = $settingsCollection->first();
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

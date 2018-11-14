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
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Service\WebhookService;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Webhook\Exception\WebhookException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\JsonResponse;

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
     */
    public function executeWebhook(Request $request, Context $context): Response
    {
        $token = $request->query->get('sw-token');
        if ($token === null) {
            throw new BadRequestHttpException('Shopware token is invalid');
        }

        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settings = $settingsCollection->first();
        if ($token !== $settings->getWebhookExecuteToken()) {
            throw new BadRequestHttpException('Shopware token is invalid');
        }

        $postData = $request->request->all();
        $this->logger->debug('[PayPal Webhook] Received webhook', ['payload' => $postData]);

        if (empty($postData)) {
            throw new BadRequestHttpException('No webhook data sent');
        }

        $webhook = Webhook::fromArray($postData);

        try {
            $this->webhookService->executeWebhook($webhook, $context);
        } catch (WebhookException $webhookException) {
            $this->logger->error(
                '[PayPal Webhook] ' . $webhookException->getMessage(),
                ['type' => $webhookException->getEventType()]
            );

            throw new BadRequestHttpException('An error occurred on executing the webhook');
        } catch (Exception $e) {
            $this->logger->error('[PayPal Webhook] ' . $e->getMessage());

            throw new BadRequestHttpException('An error occurred on executing the webhook');
        }

        return new Response();
    }
}

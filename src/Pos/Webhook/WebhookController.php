<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Webhook\Webhook;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController extends AbstractController
{
    use PosSalesChannelTrait;

    private LoggerInterface $logger;

    private WebhookService $webhookService;

    private EntityRepository $salesChannelRepository;

    /**
     * @internal
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookService $webhookService,
        EntityRepository $salesChannelRepository
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    #[Route(path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}', name: 'api.action.paypal.pos.webhook.registration.register', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function registerWebhook(string $salesChannelId, Context $context): Response
    {
        $this->webhookService->registerWebhook($salesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/paypal/pos/webhook/registration/{salesChannelId}', name: 'api.action.paypal.pos.webhook.registration.unregister', methods: ['DELETE'], defaults: ['_acl' => ['sales_channel.deleter']])]
    public function unregisterWebhook(string $salesChannelId, Context $context): Response
    {
        $this->webhookService->unregisterWebhook($salesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

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
     */
    private function tryToExecuteWebhook(Webhook $webhook, SalesChannelEntity $salesChannel, Context $context): void
    {
        try {
            $this->webhookService->executeWebhook($webhook, $salesChannel, $context);
        } catch (WebhookException $webhookException) {
            $this->logger->error(
                '[Zettle Webhook] ' . $webhookException->getMessage(),
                [
                    'type' => $webhookException->getEventName(),
                    'webhook' => \json_encode($webhook),
                ]
            );

            throw new BadRequestHttpException('An error occurred during execution of webhook');
        } catch (\Throwable $e) {
            $this->logger->error('[Zettle Webhook] ' . $e->getMessage());

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

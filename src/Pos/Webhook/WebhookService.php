<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Webhook\Subscription\CreateSubscription;
use Swag\PayPal\Pos\Api\Webhook\Subscription\UpdateSubscription;
use Swag\PayPal\Pos\Api\Webhook\Webhook;
use Swag\PayPal\Pos\Exception\InvalidContactEmailException;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class WebhookService
{
    use PosSalesChannelTrait;

    private const EMAIL_CONFIG_KEY = 'core.basicInformation.email';

    private SubscriptionResource $subscriptionResource;

    private RouterInterface $router;

    private WebhookRegistry $webhookRegistry;

    private EntityRepository $salesChannelRepository;

    private SystemConfigService $systemConfig;

    private UuidConverter $uuidConverter;

    /**
     * @internal
     */
    public function __construct(
        SubscriptionResource $subscriptionResource,
        WebhookRegistry $webhookRegistry,
        EntityRepository $salesChannelRepository,
        SystemConfigService $systemConfig,
        UuidConverter $uuidConverter,
        RouterInterface $router,
    ) {
        $this->subscriptionResource = $subscriptionResource;
        $this->webhookRegistry = $webhookRegistry;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->systemConfig = $systemConfig;
        $this->uuidConverter = $uuidConverter;
        $this->router = $router;
    }

    public function registerWebhook(string $salesChannelId, Context $context): void
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $posSalesChannel = $this->getPosSalesChannel($salesChannel);

        if ($posSalesChannel->getWebhookSigningKey() !== null) {
            $this->updateWebhook($salesChannelId, $context);

            return;
        }

        $subscription = new CreateSubscription();
        $subscription->setUuid($this->uuidConverter->convertUuidToV1($salesChannelId));
        $subscription->setTransportName('WEBHOOK');
        $subscription->setContactEmail($this->getContactEmail($salesChannelId));
        $subscription->setDestination($this->getWebhookUrl($salesChannelId));
        $subscription->setEventNames([WebhookEventNames::INVENTORY_BALANCE_CHANGED]);

        try {
            $response = $this->subscriptionResource->createWebhook($posSalesChannel, $subscription);
        } catch (PosApiException $exception) {
            if ($exception->getApiError()->getErrorType() !== PosApiError::ERROR_TYPE_SUBSCRIPTION_EXISTS) {
                throw $exception;
            }

            // we have to re-register, not update, because update doesn't deliver key
            $this->unregisterWebhook($salesChannelId, $context);
            $response = $this->subscriptionResource->createWebhook($posSalesChannel, $subscription);
        }

        if ($response === null) {
            throw new WebhookNotRegisteredException($salesChannelId);
        }

        $this->updateSigningKey($response->getSigningKey(), $salesChannel, $context);
    }

    public function unregisterWebhook(string $salesChannelId, Context $context): void
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $posSalesChannel = $this->getPosSalesChannel($salesChannel);

        if ($posSalesChannel->getWebhookSigningKey() === null) {
            return;
        }

        $this->subscriptionResource->removeWebhook($posSalesChannel, $this->uuidConverter->convertUuidToV1($salesChannelId));
        $this->updateSigningKey(null, $salesChannel, $context);
    }

    public function executeWebhook(Webhook $webhook, SalesChannelEntity $salesChannel, Context $context): void
    {
        if (!$salesChannel->getActive()) {
            return;
        }

        $webhookHandler = $this->webhookRegistry->getWebhookHandler($webhook->getEventName());
        $webhookHandler->invoke($webhook, $salesChannel, $context);
    }

    private function updateWebhook(string $salesChannelId, Context $context): void
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $posSalesChannel = $this->getPosSalesChannel($salesChannel);

        if ($posSalesChannel->getWebhookSigningKey() === null) {
            throw new WebhookIdInvalidException($salesChannelId);
        }

        $subscription = new UpdateSubscription();
        $subscription->setContactEmail($this->getContactEmail($salesChannelId));
        $subscription->setDestination($this->getWebhookUrl($salesChannelId));
        $subscription->setEventNames([WebhookEventNames::INVENTORY_BALANCE_CHANGED]);

        try {
            $this->subscriptionResource->updateWebhook(
                $posSalesChannel,
                $this->uuidConverter->convertUuidToV1($salesChannelId),
                $subscription
            );
        } catch (PosApiException $exception) {
            if ($exception->getApiError()->getErrorType() !== PosApiError::ERROR_TYPE_SUBSCRIPTION_NOT_EXISTS) {
                throw $exception;
            }

            $this->updateSigningKey(null, $salesChannel, $context);
            $this->registerWebhook($salesChannelId, $context);
        }
    }

    private function getWebhookUrl(string $salesChannelId): string
    {
        $this->router->getContext()->setScheme('https');

        return $this->router->generate(
            'api.action.paypal.pos.webhook.execute',
            ['salesChannelId' => $salesChannelId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function updateSigningKey(?string $signingKey, SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->salesChannelRepository->update([[
            'id' => $salesChannel->getId(),
            SwagPayPal::SALES_CHANNEL_POS_EXTENSION => [
                'id' => $this->getPosSalesChannel($salesChannel)->getId(),
                'webhookSigningKey' => $signingKey,
            ],
        ]], $context);
    }

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

    private function getContactEmail(string $salesChannelId): string
    {
        $contactEmail = $this->systemConfig->get(self::EMAIL_CONFIG_KEY, $salesChannelId);

        if (!\is_string($contactEmail)) {
            throw new InvalidContactEmailException($salesChannelId);
        }

        return $contactEmail;
    }
}

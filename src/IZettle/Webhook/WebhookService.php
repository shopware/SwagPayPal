<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Webhook\Subscription\CreateSubscription;
use Swag\PayPal\IZettle\Api\Webhook\Subscription\UpdateSubscription;
use Swag\PayPal\IZettle\Api\Webhook\Webhook;
use Swag\PayPal\IZettle\Resource\SubscriptionResource;
use Swag\PayPal\IZettle\Util\IZettleSalesChannelTrait;
use Swag\PayPal\IZettle\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\IZettle\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService
{
    use IZettleSalesChannelTrait;

    private const EMAIL_CONFIG_KEY = 'core.basicInformation.email';

    /**
     * @var SubscriptionResource
     */
    private $subscriptionResource;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var WebhookRegistry
     */
    private $webhookRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    public function __construct(
        SubscriptionResource $subscriptionResource,
        WebhookRegistry $webhookRegistry,
        EntityRepositoryInterface $salesChannelRepository,
        SystemConfigService $systemConfig,
        UuidConverter $uuidConverter,
        RouterInterface $router
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
        $iZettleSalesChannel = $this->getIZettleSalesChannel($salesChannel);

        if ($iZettleSalesChannel->getWebhookSigningKey() !== null) {
            $this->updateWebhook($salesChannelId, $context);

            return;
        }

        $subscription = new CreateSubscription();
        $subscription->setUuid($this->uuidConverter->convertUuidToV1($salesChannelId));
        $subscription->setTransportName('WEBHOOK');
        $subscription->setContactEmail($this->systemConfig->get(self::EMAIL_CONFIG_KEY, $salesChannelId));
        $subscription->setDestination($this->getWebhookUrl($salesChannelId));
        $subscription->setEventNames([WebhookEventNames::INVENTORY_BALANCE_CHANGED]);

        try {
            $response = $this->subscriptionResource->createWebhook($iZettleSalesChannel, $subscription);
        } catch (IZettleApiException $exception) {
            if ($exception->getApiError()->getErrorType() !== IZettleApiError::ERROR_TYPE_SUBSCRIPTION_EXISTS) {
                throw $exception;
            }

            // we have to re-register, not update, because update doesn't deliver key
            $this->unregisterWebhook($salesChannelId, $context);
            $response = $this->subscriptionResource->createWebhook($iZettleSalesChannel, $subscription);
        }

        if ($response === null) {
            throw new WebhookNotRegisteredException($salesChannelId);
        }

        $this->updateSigningKey($response->getSigningKey(), $salesChannel, $context);
    }

    public function unregisterWebhook(string $salesChannelId, Context $context): void
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $iZettleSalesChannel = $this->getIZettleSalesChannel($salesChannel);

        if ($iZettleSalesChannel->getWebhookSigningKey() === null) {
            return;
        }

        $this->subscriptionResource->removeWebhook($iZettleSalesChannel, $this->uuidConverter->convertUuidToV1($salesChannelId));
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
        $iZettleSalesChannel = $this->getIZettleSalesChannel($salesChannel);

        if ($iZettleSalesChannel->getWebhookSigningKey() === null) {
            throw new WebhookIdInvalidException($salesChannelId);
        }

        $subscription = new UpdateSubscription();
        $subscription->setContactEmail($this->systemConfig->get(self::EMAIL_CONFIG_KEY, $salesChannelId));
        $subscription->setDestination($this->getWebhookUrl($salesChannelId));
        $subscription->setEventNames([WebhookEventNames::INVENTORY_BALANCE_CHANGED]);

        try {
            $this->subscriptionResource->updateWebhook(
                $iZettleSalesChannel,
                $this->uuidConverter->convertUuidToV1($salesChannelId),
                $subscription
            );
        } catch (IZettleApiException $exception) {
            if ($exception->getApiError()->getErrorType() !== IZettleApiError::ERROR_TYPE_SUBSCRIPTION_NOT_EXISTS) {
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
            'api.action.paypal.izettle.webhook.execute',
            ['salesChannelId' => $salesChannelId, 'version' => PlatformRequest::API_VERSION],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function updateSigningKey(?string $signingKey, SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->salesChannelRepository->update([[
            'id' => $salesChannel->getId(),
            SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION => [
                'id' => $this->getIZettleSalesChannel($salesChannel)->getId(),
                'webhookSigningKey' => $signingKey,
            ],
        ]], $context);
    }

    private function getSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $criteria = (new Criteria())->setIds([$salesChannelId]);

        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new WebhookIdInvalidException($salesChannelId);
        }

        return $salesChannel;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Swag\PayPal\Pos\Api\MerchantInformation;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Api\Webhook\Subscription\CreateSubscription;
use Swag\PayPal\Pos\Api\Webhook\Subscription\SubscriptionResponse;
use Swag\PayPal\Pos\Api\Webhook\Subscription\UpdateSubscription;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;

class SubscriptionResource
{
    private PosClientFactory $posClientFactory;

    public function __construct(PosClientFactory $posClientFactory)
    {
        $this->posClientFactory = $posClientFactory;
    }

    /**
     * @deprecated tag:v4.0.0 will be removed, use UserResource instead
     */
    public function getMerchantInformation(string $apiKey): ?MerchantInformation
    {
        $client = $this->posClientFactory->getPosClient(PosBaseURL::SECURE, $apiKey);

        $response = $client->sendGetRequest(PosRequestUri::MERCHANT_INFORMATION);

        if ($response === null) {
            return null;
        }

        $information = new MerchantInformation();
        $information->assign($response);

        return $information;
    }

    public function createWebhook(PosSalesChannelEntity $salesChannelEntity, CreateSubscription $createSubscription): ?SubscriptionResponse
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PUSHER, $apiKey);

        $response = $client->sendPostRequest(PosRequestUri::SUBSCRIPTION_RESOURCE, $createSubscription);

        if ($response === null) {
            return null;
        }

        $subscription = new SubscriptionResponse();
        $subscription->assign($response);

        return $subscription;
    }

    public function updateWebhook(PosSalesChannelEntity $salesChannelEntity, string $subscriptionUuid, UpdateSubscription $updateSubscription): void
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PUSHER, $apiKey);

        $url = \sprintf(PosRequestUri::SUBSCRIPTION_RESOURCE . '%s/', $subscriptionUuid);

        $client->sendPutRequest($url, $updateSubscription);
    }

    public function removeWebhook(PosSalesChannelEntity $salesChannelEntity, string $subscriptionUuid): void
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PUSHER, $apiKey);

        $url = \sprintf(PosRequestUri::SUBSCRIPTION_RESOURCE_DELETE . '%s/', $subscriptionUuid);

        $client->sendDeleteRequest($url);
    }
}

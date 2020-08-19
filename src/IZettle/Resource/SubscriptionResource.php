<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Api\MerchantInformation;
use Swag\PayPal\IZettle\Api\Webhook\Subscription\CreateSubscription;
use Swag\PayPal\IZettle\Api\Webhook\Subscription\SubscriptionResponse;
use Swag\PayPal\IZettle\Api\Webhook\Subscription\UpdateSubscription;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

class SubscriptionResource
{
    /**
     * @var IZettleClientFactory
     */
    private $iZettleClientFactory;

    public function __construct(IZettleClientFactory $iZettleClientFactory)
    {
        $this->iZettleClientFactory = $iZettleClientFactory;
    }

    public function getMerchantInformation(string $apiKey): ?MerchantInformation
    {
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::SECURE, $apiKey);

        $response = $client->sendGetRequest(IZettleRequestUri::MERCHANT_INFORMATION);

        if ($response === null) {
            return null;
        }

        $information = new MerchantInformation();
        $information->assign($response);

        return $information;
    }

    public function createWebhook(IZettleSalesChannelEntity $salesChannelEntity, CreateSubscription $createSubscription): ?SubscriptionResponse
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PUSHER, $apiKey);

        $response = $client->sendPostRequest(IZettleRequestUri::SUBSCRIPTION_RESOURCE, $createSubscription);

        if ($response === null) {
            return null;
        }

        $subscription = new SubscriptionResponse();
        $subscription->assign($response);

        return $subscription;
    }

    public function updateWebhook(IZettleSalesChannelEntity $salesChannelEntity, string $subscriptionUuid, UpdateSubscription $updateSubscription): void
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PUSHER, $apiKey);

        $url = \sprintf(IZettleRequestUri::SUBSCRIPTION_RESOURCE . '%s/', $subscriptionUuid);

        $client->sendPutRequest($url, $updateSubscription);
    }

    public function removeWebhook(IZettleSalesChannelEntity $salesChannelEntity, string $subscriptionUuid): void
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PUSHER, $apiKey);

        $url = \sprintf(IZettleRequestUri::SUBSCRIPTION_RESOURCE_DELETE . '%s/', $subscriptionUuid);

        $client->sendDeleteRequest($url);
    }
}

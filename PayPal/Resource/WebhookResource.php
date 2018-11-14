<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\PayPal\Client\Exception\PayPalSettingsInvalidException;
use SwagPayPal\PayPal\Client\PayPalClient;
use SwagPayPal\PayPal\Component\Patch\PatchInterface;
use SwagPayPal\PayPal\RequestUri;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\HttpFoundation\Request;

class WebhookResource
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(TokenResource $tokenResource, RepositoryInterface $settingGeneralRepo)
    {
        $this->tokenResource = $tokenResource;
        $this->settingGeneralRepo = $settingGeneralRepo;
    }

    public function createWebhook(string $webhookUrl, array $webhookEvents, Context $context): string
    {
        $requestData = [
            'url' => $webhookUrl,
            'event_types' => [],
        ];

        foreach ($webhookEvents as $event) {
            $requestData['event_types'][] = [
                'name' => $event,
            ];
        }

        $paypalClient = $this->createPaymentClient($context);
        try {
            $response = $paypalClient->sendRequest(Request::METHOD_POST, RequestUri::WEBHOOK_RESOURCE, $requestData);

            return $response['id'];
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);

            if ($error['name'] === 'WEBHOOK_URL_ALREADY_EXISTS') {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            throw  $e;
        }
    }

    public function getWebhookUrl(string $webhookId, Context $context): string
    {
        $paypalClient = $this->createPaymentClient($context);
        try {
            $response = $paypalClient->sendRequest(
                Request::METHOD_GET,
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId
            );

            return $response['url'];
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);

            if ($error['name'] === 'INVALID_RESOURCE_ID') {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, Context $context): void
    {
        $requestData = [];
        $requestData[] = [
            'op' => PatchInterface::OPERATION_REPLACE,
            'path' => '/url',
            'value' => $webhookUrl,
        ];

        $paypalClient = $this->createPaymentClient($context);
        try {
            $paypalClient->sendRequest(
                Request::METHOD_PATCH,
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId,
                $requestData
            );
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);

            if ($error['name'] === 'INVALID_RESOURCE_ID') {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function createPaymentClient(Context $context): PayPalClient
    {
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settings = $settingsCollection->first();

        return new PayPalClient($this->tokenResource, $context, $settings);
    }
}

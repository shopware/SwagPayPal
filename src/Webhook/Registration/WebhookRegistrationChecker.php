<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;

/**
 * Registering uses the Administration URL, but a webhook registered for the APP_URL counts as valid too.
 *
 * @internal
 */
#[Package('checkout')]
class WebhookRegistrationChecker
{
    public function __construct(
        private readonly WebhookResource $webhookResource,
        private readonly SystemConfigService $systemConfigService,
        private readonly WebhookUrlBuilder $urlBuilder,
    ) {
    }

    /**
     * PayPal's errors, e.g. invalid credentials, aren't caught.
     */
    public function check(?string $salesChannelId): WebhookRegistration
    {
        if ($this->systemConfigService->getBool(Settings::IS_LOCAL_ENVIRONMENT, $salesChannelId)) {
            return new WebhookRegistration(WebhookRegistrationStatus::Disabled);
        }

        $token = $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);
        $expectedUrls = $token === '' ? [] : $this->urlBuilder->buildAll($token);

        $webhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId);
        if ($webhookId === '') {
            return new WebhookRegistration(WebhookRegistrationStatus::Missing);
        }

        try {
            $registeredUrl = $this->webhookResource->getWebhookUrl($webhookId, $salesChannelId);
        } catch (WebhookIdInvalidException) {
            return new WebhookRegistration(WebhookRegistrationStatus::Missing);
        }

        if (\in_array($registeredUrl, $expectedUrls, true)) {
            return new WebhookRegistration(WebhookRegistrationStatus::Valid, $registeredUrl);
        }

        $maskedUrl = WebhookUrlBuilder::maskToken($registeredUrl);

        return new WebhookRegistration(
            WebhookRegistrationStatus::Invalid,
            $registeredUrl,
            \in_array($maskedUrl, \array_map(WebhookUrlBuilder::maskToken(...), $expectedUrls), true),
        );
    }
}

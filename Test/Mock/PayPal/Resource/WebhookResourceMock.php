<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Resource\WebhookResource;
use SwagPayPal\Service\WebhookService;
use SwagPayPal\Test\Mock\Repositories\SwagPayPalSettingGeneralRepoMock;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookResourceMock extends WebhookResource
{
    public const CREATED_WEBHOOK_ID = 'createdWebhookId';

    public const THROW_WEBHOOK_ID_INVALID = 'webhookIdInvalid';

    public const THROW_WEBHOOK_ALREADY_EXISTS = 'webhookAlreadyExists';

    public function createWebhook(string $webhookUrl, array $webhookEvents, Context $context): string
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ALREADY_EXISTS)) {
            throw new WebhookAlreadyExistsException('');
        }

        if ($context->hasExtension(SwagPayPalSettingGeneralRepoMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID)) {
            return self::CREATED_WEBHOOK_ID;
        }

        return SwagPayPalSettingGeneralRepoMock::ALREADY_EXISTING_WEBHOOK_ID;
    }

    public function getWebhookUrl(string $webhookId, Context $context): string
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ID_INVALID)) {
            throw new WebhookIdInvalidException('');
        }

        return WebhookService::PAYPAL_WEBHOOK_ROUTE .
            '?' .
            WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME .
            '=' .
            SwagPayPalSettingGeneralRepoMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN;
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, Context $context): void
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ID_INVALID)) {
            throw new WebhookIdInvalidException('');
        }
    }
}

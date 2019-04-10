<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\WebhookService;

class WebhookResourceMock extends WebhookResource
{
    public const CREATED_WEBHOOK_ID = 'createdWebhookId';

    public const THROW_WEBHOOK_ID_INVALID = 'webhookIdInvalid';

    public const THROW_WEBHOOK_ALREADY_EXISTS = 'webhookAlreadyExists';

    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, Context $context): string
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ALREADY_EXISTS)) {
            throw new WebhookAlreadyExistsException('');
        }

        if ($context->hasExtension(SettingsServiceMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID)) {
            return self::CREATED_WEBHOOK_ID;
        }

        return SettingsServiceMock::ALREADY_EXISTING_WEBHOOK_ID;
    }

    public function getWebhookUrl(string $webhookId, Context $context): string
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ID_INVALID)) {
            throw new WebhookIdInvalidException('');
        }

        return WebhookService::PAYPAL_WEBHOOK_ROUTE . '?'
            . WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME . '='
            . SettingsServiceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN;
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, Context $context): void
    {
        if ($context->hasExtension(self::THROW_WEBHOOK_ID_INVALID)) {
            throw new WebhookIdInvalidException('');
        }
    }
}

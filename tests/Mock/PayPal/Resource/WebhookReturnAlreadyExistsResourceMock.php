<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\PayPal\Api\CreateWebhooks;

class WebhookReturnAlreadyExistsResourceMock extends WebhookReturnCreatedResourceMock
{
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        return self::ALREADY_EXISTING_WEBHOOK_ID;
    }
}

<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Webhook;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\Webhook\WebhookControllerTest;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceMock implements WebhookServiceInterface
{
    public function registerWebhook(?string $salesChannelId): string
    {
        return WebhookService::WEBHOOK_CREATED;
    }

    public function executeWebhook(Webhook $webhook, Context $context): void
    {
        if ($context->hasExtension(WebhookControllerTest::THROW_WEBHOOK_EXCEPTION)) {
            throw new WebhookException(DummyWebhook::EVENT_TYPE, 'testWebhookExceptionMessage');
        }

        if ($context->hasExtension(WebhookControllerTest::THROW_GENERAL_EXCEPTION)) {
            throw new \RuntimeException('testGeneralExceptionMessage');
        }
    }
}

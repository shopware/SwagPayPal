<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Webhook;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Test\Controller\WebhookControllerTest;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\Exception\WebhookException;
use SwagPayPal\Webhook\WebhookService;
use SwagPayPal\Webhook\WebhookServiceInterface;

class WebhookServiceMock implements WebhookServiceInterface
{
    public function registerWebhook(Context $context): string
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

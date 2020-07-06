<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Webhook;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\ApiV1\Api\Webhook;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\Webhook\WebhookControllerTest;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceMock implements WebhookServiceInterface
{
    /**
     * @var string[]
     */
    private $registrations = [];

    /**
     * @var string[]
     */
    private $deregistrations = [];

    public function __construct()
    {
    }

    public function registerWebhook(?string $salesChannelId): string
    {
        $this->registrations[] = $salesChannelId ?? 'null';

        return WebhookService::WEBHOOK_CREATED;
    }

    public function deregisterWebhook(?string $salesChannelId, ?SwagPayPalSettingStruct $settings = null): string
    {
        if ($settings === null || $settings->getWebhookId() === null) {
            return WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
        }

        $this->deregistrations[] = $salesChannelId ?? 'null';

        return WebhookService::WEBHOOK_DELETED;
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

    /**
     * @return string[]
     */
    public function getRegistrations(): array
    {
        return $this->registrations;
    }

    /**
     * @return string[]
     */
    public function getDeregistrations(): array
    {
        return $this->deregistrations;
    }
}

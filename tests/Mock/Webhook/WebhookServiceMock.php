<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\Webhook\WebhookControllerTest;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookServiceMock implements WebhookServiceInterface
{
    /**
     * @var string[]
     */
    private array $registrations = [];

    /**
     * @var string[]
     */
    private array $deregistrations = [];

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getStatus(?string $salesChannelId): string
    {
        return WebhookService::STATUS_WEBHOOK_VALID;
    }

    public function registerWebhook(?string $salesChannelId): string
    {
        $this->registrations[] = $salesChannelId ?? 'null';

        return WebhookService::WEBHOOK_CREATED;
    }

    public function deregisterWebhook(?string $salesChannelId): string
    {
        $this->deregistrations[] = $salesChannelId ?? 'null';

        if ($this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId) === '') {
            return WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
        }

        return WebhookService::WEBHOOK_DELETED;
    }

    public function executeWebhook(Webhook $webhook, Context $context): void
    {
        if ($context->hasExtension(WebhookControllerTest::THROW_PAYPAL_API_EXCEPTION)) {
            throw new PayPalApiException('testPayPalApiExceptionMessage', 'testPayPalApiExceptionMessage');
        }

        if ($context->hasExtension(WebhookControllerTest::THROW_WEBHOOK_EXCEPTION)) {
            throw new WebhookException(DummyWebhook::EVENT_TYPE, 'testWebhookExceptionMessage');
        }

        if ($context->hasExtension(WebhookControllerTest::THROW_NOT_FOUND_EXCEPTION)) {
            throw new WebhookHandlerNotFoundException(DummyWebhook::EVENT_TYPE);
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

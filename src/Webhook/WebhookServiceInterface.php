<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Webhook\Exception\WebhookException;

#[Package('checkout')]
interface WebhookServiceInterface
{
    public function getStatus(?string $salesChannelId): string;

    public function registerWebhook(?string $salesChannelId): string;

    public function deregisterWebhook(?string $salesChannelId): string;

    /**
     * @param WebhookV1|WebhookV2 $webhook
     *
     * @throws WebhookException if no transaction could be found to the given Webhook
     * @throws \Exception
     */
    public function executeWebhook(PayPalApiStruct $webhook, Context $context): void;
}

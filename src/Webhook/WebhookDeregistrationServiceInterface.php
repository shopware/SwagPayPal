<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Swag\PayPal\Setting\SwagPayPalSettingStruct;

/**
 * @deprecated tag:v2.0.0 - Will be merged into WebhookService
 */
interface WebhookDeregistrationServiceInterface
{
    public function deregisterWebhook(?string $salesChannelId, ?SwagPayPalSettingStruct $settings = null): string;
}

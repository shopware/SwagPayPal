<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Webhook\Exception\WebhookException;

interface WebhookServiceInterface
{
    public function registerWebhook(Context $context): string;

    /**
     * @throws WebhookException if no transaction could be found to the given Webhook
     */
    public function executeWebhook(Webhook $webhook, Context $context): void;
}

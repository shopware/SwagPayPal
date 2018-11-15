<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Webhook;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Webhook\WebhookHandler;

class DummyWebhook implements WebhookHandler
{
    public const EVENT_TYPE = 'PAYPAL_TEST_WEBHOOK';

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
    }
}

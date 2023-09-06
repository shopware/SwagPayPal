<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class CaptureReversed extends CaptureRefunded
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_CAPTURE_REVERSED;
    }
}

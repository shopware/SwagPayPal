<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Recording;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_last_webhook',
    description: 'The last webhook PayPal sent to the shop',
    required: ['at', 'eventType', 'outcome', 'eventId'],
    properties: [
        new OA\Property(property: 'at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'eventType', description: 'For example `PAYMENT.CAPTURE.COMPLETED`', type: 'string'),
        new OA\Property(property: 'outcome', description: '`handled` when the shop processed the webhook, `unknown_transaction` when it is about a payment the shop doesn\'t know, `failed` when the shop couldn\'t process it', type: 'string', enum: ['handled', 'unknown_transaction', 'failed']),
        new OA\Property(property: 'eventId', description: 'The ID of PayPal\'s event. Empty when the payload had none', type: 'string'),
    ],
)]
#[Package('checkout')]
final class LastWebhook extends Struct
{
    public function __construct(
        public readonly \DateTimeImmutable $at,
        public readonly string $eventType,
        public readonly WebhookOutcome $outcome,
        public readonly string $eventId,
    ) {
    }
}
